<?php

namespace HyperPDF\Nodes;

class StyleNode
{
    protected $children;
    protected $styles = [];
    protected $text = null;
    protected $name;
    protected $parent;

    public function __construct($rules)
    {
        $this->children = [];

        // a leaf
        if (isset($rules['name']) && !isset($rules['>'])) {
            $this->setAll($rules);

            return;
        }

        // second level to a leaf
        if (isset($rules['name'])) {
            $this->setAll($rules);
            $this->setStyleCompletion();

            // node with name '*' is a directive node
            // it defines style for all of its siblings reguardless their different name
            $directiveNode;

            foreach ($rules['>'] as $rule) {
                $styleNode = new self($rule);

                // we need to copy the style defined in the directive node to each of its siblings
                // by best practice we define directive node at the beginning of its kind
                if (!isset($directiveNode) && $styleNode->isDiretiveNode()) {
                    $directiveNode = $styleNode;
                    $directiveNode->setStyleCompletion();
                }

                // given such a directive node is defined
                // we "patch" its siblings with directive node's style
                if (isset($directiveNode)) {
                    $styleNode->setStyles(array_merge($directiveNode->getStyles(), $styleNode->getStyles()));
                }

                // only non-directive node is consider as child node
                // then link them with the parent node
                if (!$styleNode->isDiretiveNode()) {
                    $styleNode->setParent($this);
                    $this->addChild($styleNode);
                }
            }

            return;
        }

        foreach ($rules as $rule) {
            $this->addChild(new self($rule));
        }
    }

    // accessors
    //
    // booleans
    //
    public function hasChildren($rule)
    {
        if (isset($rule['>']) && $rule['>']) {
            return true;
        }

        return false;
    }

    public function isDiretiveNode()
    {
        return ($this->name == '*');
    }

    // getters
    //
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

    public function getName()
    {
        return $this->name;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getStyle($name)
    {
        return isset($this->styles[$name]) ? $this->styles[$name] : null;
    }

    public function getStyles()
    {
        return $this->styles;
    }

    public function getAncestorsByStyle($name)
    {
        $ancestors = [];
        $node = $this;

        while ($parent = $node->getParent()) {
            if ($style = $parent->getStyle($name)) {
                $ancestors[] = $parent;
            }

            $node = $parent;
        }

        return $ancestors;
    }

    public function getInheritedStyle($name)
    {
        $prop = null;
        $ancestors = $this->getAncestorsByStyle($name);

        while ($ancestors) {
            $node = array_pop($ancestors);

            // nomally we should use the lately defined style to overwrite previous
            // but position is not working like this way
            if ($name != 'left' || $name != 'top' || $name != 'position' || $name != 'width' || $name != 'height') {
                $prop = $node->getStyle($name);
            }
        }

        return $prop;
    }

    public function getText()
    {
        return $this->text;
    }

    // mutators
    //
    public function addChild(StyleNode $styleNode)
    {
        $this->children[] = $styleNode;
    }

    public function setParent(StyleNode $parent)
    {
        $this->parent = $parent;
    }

    public function setStyles(array $styles = [])
    {
        $this->styles = $styles;
        $this->setStyleCompletion();
    }

    public function setStyle($name, $value)
    {
        $this->styles[$name] = $value;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    protected function setAll($rule)
    {
        $this->name = isset($rule['name']) ? $rule['name'] : null;

        $this->styles = isset($rule['style']) ? $rule['style'] : [];

        $this->text = isset($rule['text']) ? $rule['text'] : null;
    }

    protected function setStyleCompletion()
    {
        // add defaults if style not specified
        $this->styles = array_merge([
            'position' => 'relative',
            'left' => '0pt',
            'top' => '0pt',
            'width' => '100%',
            'background-color' => '#ffffff',
            'color' => '#000000',
            'font-family' => 'Arial',
            'font-size' => '10pt',
            'font-style' => 'normal',
            'font-weight' => 'normal',
            'border' => 'none',
            'text-align' => 'left',
            'line-height' => '5pt',
        ], $this->styles);
    }
}
