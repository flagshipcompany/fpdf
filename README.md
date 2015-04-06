# flagshipcompany/fpdf

*FORKED FROM* [hanneskod/fpdf](https://github.com/hanneskod/fpdf)
 
### Composer Installation

Add this to to your composer.json file:

```
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

```
	$laserPdf = new FcsFpdf('P', 'in', [11, 8.5]);
    $laserPdf->SetMargins(1, 1);
    // ....
```