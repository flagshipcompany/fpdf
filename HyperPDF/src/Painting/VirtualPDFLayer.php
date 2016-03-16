<?php

namespace HyperPDF\Painting;

use HyperPDF\Nodes\LayoutNode;
use HyperPDF\SimpleFPDF;

class VirtualPDFLayer
{
    protected $offsetX;
    protected $offsetY;
    protected $pdf;
    protected $layout;
    protected $documentWidth;
    protected $children;
    protected $childrenCommon;
    protected $parent;

    public function __construct(SimpleFPDF $pdf, LayoutNode $layout)
    {
        $this->offsetX = 0;
        $this->offsetY = 0;
        $this->children = [];
        $this->childrenCommon = [];
        $this->parent = null;

        $this->pdf = $pdf;
        $this->layout = $layout;

        $this->documentWidth = $pdf->w - $pdf->lMargin - $pdf->rMargin;

        $this->setCurrentLayerOffsets();

        $childLayouts = $layout->getChildren();

        $parentOffsetX = $this->getOffsetX();
        $parentOffsetY = $this->getOffsetY();

        foreach ($childLayouts as $childLayout) {
            $layer = new self($pdf, $childLayout);
            $layerWidth = $layer->getLayerWidth();

            if ($layer->getLayoutProperty('lineBreak') === false) {
                $layer->setOffsetX($parentOffsetX);
                $parentOffsetX += $layerWidth;
            }

            $this->children[] = $layer;
            $layer->setParent($this);
        }
    }

    public function getOffSetX()
    {
        return $this->offsetX;
    }

    public function getOffSetY()
    {
        return $this->offsetY;
    }

    public function setOffSetX($x)
    {
        $this->offsetX = $x;

        return $this;
    }

    public function setOffSetY($y)
    {
        $this->offsetY = $y;

        return $this;
    }

    public function setOffsetXY($x, $y)
    {
        $this->setOffSetX($x);
        $this->setOffSetY($y);
    }

    public function setParent(VirtualPDFLayer $parent)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getLayoutProperty($name)
    {
        return $this->layout->getProperty($name);
    }

    public function getLayoutOffset($type = 'x')
    {
        $value = $this->layout->getProperty($type);

        if (!is_array($value)) {
            return $value;
        }

        $offset = 0;

        foreach ($value as $v) {
            if ($v <= 1) {
                $offset += $v * $this->documentWidth;
                continue;
            }

            $offset += $v;
        }

        return $offset;
    }

    public function getLayerWidth()
    {
        $wdiths = [];
        $widths[] = $this->getLayoutProperty('width');

        $layer = $this->getParent();

        while ($layer) {
            $widths[] = $layer->getLayoutProperty('width');
            $layer = $layer->getParent();
        }

        $width = $this->documentWidth;

        while ($widths) {
            $w = array_pop($widths);

            if ($w <= 1) {
                $width *= $w;
                continue;
            }

            $width = $w;
        }

        return $width;
    }

    /**
     * getCumulativeOffsets get all ancestors cumulative summation of offset X and Y.
     *
     * @return array
     */
    public function getCumulativeOffsets()
    {
        $parentOffset = [
            'offsetX' => 0,
            'offsetY' => 0,
        ];

        $layer = $this->getParent();

        while ($layer) {
            $parentOffset['offsetX'] += $layer->getLayoutOffset('x');
            $parentOffset['offsetY'] += $layer->getLayoutOffset('y');

            $layer = $layer->getParent();
        }

        return $parentOffset;
    }

    /**
     * toArray output all subtree nodes (including current node its self) into an array.
     *
     * @return array
     */
    public function toArray()
    {
        $list = [];

        $list[spl_object_hash($this)] = $this;

        foreach ($this->getChildren() as $child) {
            $list[spl_object_hash($child)] = $child;
            $list = array_merge($list, $child->toArray());
        }

        return $list;
    }

    public function setCurrentLayerOffsets()
    {
        $offsetX = $this->getLayoutOffset('x');
        $offsetY = $this->getLayoutOffset('y');

        $cumulativeOffsets = $this->getCumulativeOffsets();

        $offsetX += $cumulativeOffsets['offsetX'];
        $offsetY += $cumulativeOffsets['offsetY'];

        $this->setOffsetX($offsetX + $this->pdf->lMargin);
        $this->setOffsetY($offsetY + $this->pdf->tMargin);
    }

    public function draw()
    {
        if (!$this->layout->getProperties()) {
            return;
        }

        if ($textColor = $this->hex2rgb($this->layout->getProperty('textColor'))) {
            $this->pdf->SetTextColor($textColor['r'], $textColor['g'], $textColor['b']);
        }

        if ($drawColor = $this->hex2rgb($this->layout->getProperty('drawColor'))) {
            $this->pdf->SetDrawColor($drawColor['r'], $drawColor['g'], $drawColor['b']);
        }

        $fillColor = $this->hex2rgb($this->layout->getProperty('fillColor'));
        if ($fillColor) {
            $this->pdf->SetFillColor($fillColor['r'], $fillColor['g'], $fillColor['b']);
        }

        if ($font = $this->layout->getProperty('font')) {
            $this->pdf->SetFont(ucfirst($font['family']), $font['style'], $font['size']);
        }

        $this->pdf->SetXY($this->getOffsetX(), $this->getOffsetY());

        $this->pdf->MultiCell(
            $this->getLayerWidth(),                     // width
            $this->layout->getProperty('height'),       // height
            $this->layout->getStyleNode()->getText(),   // text | label
            $this->layout->getProperty('border'),       // border
            $this->layout->getProperty('align'),        // alignment
            !!$fillColor                                // fill background or not
        );

        if ($this->layout->getProperty('breakLine')) {
            $this->pdf->Ln();

            $cumulativeOffsets = $this->getCumulativeOffsets();
            $this->setOffsetX($cumulativeOffsets['offsetX']);

            return;
        }
    }

    protected function hex2rgb($hex)
    {
        if (!$hex) {
            return;
        }

        $hex = substr($hex, -6);
        $rgb = [
            'r' => 0,
            'g' => 0,
            'b' => 0,
        ];

        $rgb['r'] = hexdec(substr($hex, 0, 2));
        $rgb['g'] = hexdec(substr($hex, 2, 2));
        $rgb['b'] = hexdec(substr($hex, 4, 2));

        return $rgb;
    }
}
