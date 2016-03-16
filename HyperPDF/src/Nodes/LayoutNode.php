<?php

namespace HyperPDF\Nodes;

class LayoutNode
{
    protected $properties;
    protected $children;
    protected $name = null;
    protected $drawFunc;
    protected $styleNode;

    public function __construct(StyleNode $styleNode)
    {
        $this->properties = [];
        $this->children = [];
        $this->styleNode = $styleNode;

        $this->name = $styleNode->getName();
        $this->parseStyleToProp($styleNode);

        $children = $styleNode->getChildren();

        foreach ($children as $child) {
            $this->children[] = new self($child);
        }
    }

    public function getProperty($prop)
    {
        if (isset($this->properties[$prop])) {
            return $this->properties[$prop];
        }

        return;
    }

    public function setProperty($prop, $value)
    {
        $this->properties[$prop] = $value;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function getStyleNode()
    {
        return $this->styleNode;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getFirstChild()
    {
        if ($this->children) {
            return $this->children[0];
        }

        return;
    }

    protected function parseStyleToProp()
    {
        $styles = $this->styleNode->getStyles();

        foreach ($styles as $keyword => $definition) {
            $this->matchStyle($keyword, $definition);
        }
    }

    protected function matchStyle($keyword, $definition)
    {
        $keyword = strtolower($keyword);
        $definition = strtolower($definition);

        $instruction = [];

        switch ($keyword) {
            case 'text-align':
                $instruction['name'] = 'align';
                $instruction['value'] = 'J'; // justified

                if ($definition == 'left') {
                    $instruction['value'] = 'L';
                    break;
                }

                if ($definition == 'right') {
                    $instruction['value'] = 'R';
                    break;
                }

                if ($definition == 'center') {
                    $instruction['value'] = 'C';
                    break;
                }

                if ($definition == 'justify') {
                    $instruction['value'] = 'J';
                    break;
                }

                if ($definition == 'initial') {
                    break;
                }

                // inherit
                $parent = $this->styleNode->getParent();

                if ($parent && $style = $parent->getStyle('text-align')) {
                    $this->matchStyle('text-align', $style['text-align']);
                }

                break;
            case 'border':
                $instruction['name'] = 'border';
                $instruction['value'] = 0; // no border

                if ($definition == 'none') {
                    break;
                }

                list($width, $style, $color) = explode(' ', $definition);

                $this->matchStyle('border-color', $color);
                $instruction['value'] = 'LTRB';
                break;
            case 'border-color':
                $instruction['name'] = 'drawColor';
                $instruction['value'] = $definition;
                break;
            case 'border-top':
            case 'border-bottom':
            case 'border-left':
            case 'border-right':
                $position = strtoupper(substr($keyword, 7, 1));
                $instruction['name'] = 'border';
                $instruction['value'] = $this->getProperty('border') ?: 0;

                if ($definition == 'none') {
                    $this->matchStyle($keyword.'-width', '0pt');
                    $this->matchStyle($keyword.'-color', $this->getProperty('drawColor') ?: '#ffffff');
                    break;
                }

                list($width, $style, $color) = explode(' ', $definition);
                $this->matchStyle($keyword.'-color', $color);
                $this->matchStyle($keyword.'-width', $width);
                break;
            case 'border-top-color':
            case 'border-left-color':
            case 'border-right-color':
            case 'border-bottom-color':
                $this->matchStyle('border-color', $definition);
                break;
            case 'border-top-width':
            case 'border-left-width':
            case 'border-right-width':
            case 'border-bottom-width':
                $width = preg_replace('/([a-z])/', '', $definition);
                $borderType = strtoupper(substr($keyword, 7, 1)); // L, T, R, B

                $instruction['name'] = 'border';
                $instruction['value'] = $this->getProperty('border') ?: 0;

                if (!$width && !$instruction['value']) {
                    break;
                }

                if (!$width) {
                    $instruction['value'] = str_replace($borderType, '', $instruction['value']) ?: 0;
                    break;
                }

                if (!$instruction['value'] && $width) {
                    $instruction['value'] = $borderType;
                    break;
                }

                $instruction['value'] = str_replace($borderType, '', $instruction['value']).$borderType;
                break;
            case 'background-color':
                $instruction['name'] = 'fillColor';
                $instruction['value'] = $definition;
                break;
            case 'font-style':
                $instruction['name'] = 'font';
                $instruction['value'] = $this->getProperty('font') ?: [
                    'family' => 'Arial',
                    'style' => '',
                    'size' => 12,
                ];

                if ($definition == 'italic' || $definition == 'oblique') {
                    $instruction['value']['style'] = str_replace('I', '', $instruction['value']['style']).'I';
                    break;
                }

                if ($definition == 'normal' || $definition == 'initial') {
                    $instruction['value']['style'] = str_replace('I', '', $instruction['value']['style']);
                    break;
                }

                $parent = $this->styleNode->getParent();

                if ($parent && $style = $parent->getStyle('font-style')) {
                    $this->matchStyle('font-style', $style['font-style']);
                }

                break;
            case 'font-family':
                $instruction['name'] = 'font';
                $instruction['value'] = $this->getProperty('font') ?:  [
                    'family' => 'Arial',
                    'style' => '',
                    'size' => 12,
                ];

                $instruction['value']['family'] = $definition;
                break;
            case 'font-size':
                $instruction['name'] = 'font';
                $instruction['value'] = $this->getProperty('font') ?:  [
                    'family' => 'Arial',
                    'style' => '',
                    'size' => 12,
                ];

                $instruction['value']['size'] = preg_replace('/([a-z])/', '', $definition);
                break;
            case 'font-weight':
                $instruction['name'] = 'font';
                $instruction['value'] = $this->getProperty('font') ?:  [
                    'family' => 'Arial',
                    'style' => '',
                    'size' => 12,
                ];

                $instruction['value']['style'] = str_replace('B', '', $instruction['value']['style']);

                if ($definition == 'normal' || $definition == 'initial') {
                    break;
                }

                if ($definition == 'bold' || $definition > 600) {
                    $instruction['value']['style'] .= 'B';
                }

                break;
            case 'color':
                $instruction['name'] = 'textColor';
                $instruction['value'] = $definition;
                break;
            case 'width':
                $instruction['name'] = 'width';
                $instruction['value'] = preg_replace('/([a-z])/', '', $definition);

                if (strpos($instruction['value'], '%') !== false) {
                    $instruction['value'] = floatval($instruction['value']) / 100;
                }
                break;
            case 'height':
                $instruction['name'] = 'height';
                $instruction['value'] = preg_replace('/([a-z])/', '', $definition);
                break;
            case 'line-height':
                $instruction['name'] = 'height';
                $instruction['value'] = preg_replace('/([a-z])/', '', $definition);
                break;
            case 'left':
                $instruction['name'] = 'x';
                $position = $this->styleNode->getStyle('position');
                if ($position == 'absolute') {
                    $instruction['value'] = preg_replace('/([a-z])/', '', $definition);
                    break;
                }

                // relative position
                $inherits = $this->styleNode->getAncestorsByStyle('left');

                $instruction['value'] = [];

                foreach ($inherits as $inherit) {
                    $instruction['value'][] = preg_replace('/([a-z])/', '', $inherit->getStyle('left'));
                }

                break;
            case 'top':
                $instruction['name'] = 'y';
                $position = $this->styleNode->getStyle('position');
                if ($position == 'absolute') {
                    $instruction['value'] = preg_replace('/([a-z])/', '', $definition);
                    break;
                }

                // relative position
                $inherits = $this->styleNode->getAncestorsByStyle('top');

                $instruction['value'] = [];

                foreach ($inherits as $inherit) {
                    $instruction['value'][] = preg_replace('/([a-z])/', '', $inherit->getStyle('top'));
                }

                break;
            case 'display':
                $instruction['name'] = 'lineBreak';
                $instruction['value'] = ($definition == 'block');
                break;
        }

        if ($instruction) {
            $this->properties[$instruction['name']] = $instruction['value'];
        }
    }
}
