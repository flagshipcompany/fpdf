# flagshipcompany/fpdf

*FORKED FROM* [hanneskod/fpdf](https://github.com/hanneskod/fpdf)


* Includes functions to rotate text and images
* Concatenates PDF files
* Supports PDF > 1.4 by combining repositories: [pauln/tcpdi_parser](https://github.com/pauln/tcpdi_parser), [pauln/tcpdi](https://github.com/pauln/tcpdi) and [tecnickcom/tcpdf](https://github.com/tecnickcom/tcpdf)


### Composer Installation

Add this to to your composer.json file:

```json
"repositories": [
  {
    "type": "vcs",
    "url": "git@github.com:flagshipcompany/fpdf"
  }
]
```

then, require it:

```composer require flagshipcompany/fpdf @dev```


### Usage

```php
    $laserPdf = new \Fpdf\FcsFpdf('P', 'in', [11, 8.5]);
    $laserPdf->SetMargins(1, 1);
    
    // ...

    // Concatenation: ----------------------

    $pdf = new \Fpdi\ConcatPdf();
    $pdf->setFiles($filePaths);
    $pdf->concat();

    // ....
```