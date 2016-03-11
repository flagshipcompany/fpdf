<?php

namespace HyperPDF\Painting;

use HyperPDF\SimpleFPDF;
use HyperPDF\Nodes\LayoutNode;

class LayoutBlock
{
    protected $width;
    protected $height;
    protected $leftMargin;
    protected $rightMargin;

    protected $pdf;
    protected $layout;

    public function __construct(SimpleFPDF $pdf, LayoutNode $layout)
    {
        $this->pdf = $pdf;
        $this->layout = $layout;

        $this->width = $this->pdf->w - $this->pdf->lMargin - $this->pdf->rMargin;

        $this->height = $this->layout->getProperty('height') ?: 5;
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

        $w = $this->layout->getProperty('width');
        if ($w < 1) {
            $w = $w * $this->width;
        }

        $absX = $this->layout->getProperty('x');
        $absY = $this->layout->getProperty('y');

        if ($absX && $absY) {
            $this->pdf->SetXY($absX, $absY);
        }

        $x = $absX ?: $this->pdf->GetX();
        $y = $absY ?: $this->pdf->GetY();

        $this->pdf->MultiCell(
            $w,                                         // width
            $this->layout->getProperty('height'),       // height
            $this->layout->getStyleNode()->getText(),   // text | label
            $this->layout->getProperty('border'),       // border
            $this->layout->getProperty('align'),        // alignment
            !!$fillColor                                // fill background or not
        );

        $this->pdf->SetXY($x + $w, $y);
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
