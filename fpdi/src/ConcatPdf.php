<?php

namespace Fpdi;

class ConcatPdf extends \TCPDI
{
    public $files = [];

    public function setFiles($files)
    {
        $this->files = $files;

        // https://stackoverflow.com/questions/10523472/fpdi-merge-pdf-files-strange-line-appears
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
    }

    public function concat($resizeMode = null)
    {
        foreach ($this->files as $file) {
            $pagecount = $this->setSourceFile($file);
            for ($i = 1; $i <= $pagecount; ++$i) {
                $tplidx = $this->ImportPage($i);

                $sourceSize = $this->getTemplatesize($tplidx);
                $orientation = ($sourceSize['h'] >= $sourceSize['w']) ? 'P' : 'L';

                if (!isset($resizeMode) || !($resizeMode === 'P' | $resizeMode === 'L')) {
                    $this->AddPage($orientation, array($sourceSize['w'], $sourceSize['h']));
                    $this->useTemplate($tplidx);
                } else {
                    $this->AddPage($resizeMode);
                    $this->useTemplate($tplidx, 0, 0, $sourceSize['w'], $sourceSize['h'], false);
                }
            }
        }
    }
}
