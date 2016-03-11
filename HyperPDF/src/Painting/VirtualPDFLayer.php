<?php

namespace HyperPDF\Painting;

use HyperPDF\Nodes\LayoutNode;
use HyperPDF\SimpleFPDF;

class VirtualPDFLayer
{
    protected $x;
    protected $y;
    protected $pdf;
    protected $layout;

    public function __construct(SimpleFPDF $pdf, LayoutNode $layout)
    {
        $this->x = 0;
        $this->y = 0;

        $this->pdf = $pdf;
        $this->layout = $layout;
    }

    public function getX()
    {
        return $this->x;
    }

    public function getY()
    {
        return $this->y;
    }

    public function setX($x)
    {
        $this->x = $x;

        return $this;
    }

    public function setY($y)
    {
        $this->y = $y;

        return $this;
    }

    public function getLayoutBlock()
    {
        $this->layout->setProperty('x', $this->x);
        $this->layout->setProperty('y', $this->y);

        return new LayoutBlock($this->pdf, $this->layout);
    }

    public static function getDisplayLayers(SimpleFPDF $pdf, LayoutNode $layout)
    {
        $layers = [];

        $layer = new self($pdf, $layout);
        $layer->setX(($layout->getProperty('left') ?: 0) + $pdf->getX());
        $layer->setY(($layout->getProperty('top') ?: 0) + $pdf->getY());

        $childLayouts = $layout->getChildren();

        if (!$childLayouts) {
            $layers[] = $layer;

            return $layers;
        }

        // assuming all child nodes of certain parent node is similar (most often in same row or column)
        // table's td (all in the same row, meaning very likely identical Y coordinate )
        // table's tr (all in the same column, meaning very likely identical X coordinate )
        $cumulativeX = 0;

        foreach ($layout->getChildren() as $childLayout) {
            $childLayers = self::getDisplayLayers($pdf, $childLayout);

            if ($childLayers && count($childLayers) == 1) {
                $x = ($cumulativeX <= 1 ? $cumulativeX * ($pdf->w - $pdf->lMargin - $pdf->rMargin) : $cumulativeX);

                $childLayers[0]->setX($x);
            }

            if ($childLayers) {
                $layers = array_merge($layers, $childLayers);
            }

            $cumulativeX += $childLayout->getProperty('width') ?: 0;
        }

        return $layers;
    }
}
