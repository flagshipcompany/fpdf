<?php

namespace HyperPDF\Nodes;

class StyleNode
{
    protected $children;
    protected $style = [];
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

            // node with name '*' is a directive node
            // it defines style for all of its siblings reguardless their different name
            $directiveNode;

            foreach ($rules['>'] as $rule) {
                $styleNode = new self($rule);

                // we need to copy the style defined in the directive node to each of its siblings
                // by best practice we define directive node at the beginning of its kind
                if (!isset($directiveNode) && $styleNode->isDiretiveNode()) {
                    $directiveNode = $styleNode;
                }

                // given such a directive node is defined
                // we "patch" its siblings with directive node's style
                if (isset($directiveNode)) {
                    $styleNode->setStyle(array_merge($directiveNode->getStyle(), $styleNode->getStyle()));
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

    public function getStyle()
    {
        return $this->style;
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

    public function setStyle(array $style = [])
    {
        $this->style = $style;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    protected function setAll($rule)
    {
        $this->name = isset($rule['name']) ? $rule['name'] : null;
        $this->style = isset($rule['style']) ? $rule['style'] : [];
        $this->text = isset($rule['text']) ? $rule['text'] : null;
    }
}
