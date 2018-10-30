# Configuration

Events uses the [Dompdf](https://github.com/dompdf/dompdf/) library implemented in Craft Commerce to generate PDF files. You can manage some of the Dompdf options through a configuration file.

Create a file named `events.php` in your `craft/config` directory. As with any configuration option, this supports multi-environment options.

```php
return array(
    '*' => array(
        // 'letter', 'legal', 'A4', etc.
        'pdfPaperSize' => 'letter',

        // 'portrait' or 'landscape'
        'pdfPaperOrientation' => 'portrait',

        // true|false
        'pdfAllowRemoteImages' => true,
    )
);
```  

### Configuration options

- `pdfPaperSize` - handles the PDF paper size. You can find a full list of available sizes under [Dompdf\\Adapter\\CPDF::$PAPER\_SIZES](https://github.com/dompdf/dompdf/blob/master/src/Adapter/CPDF.php)
- `pdfPaperOrientation` - the PDF paper orientation, either `portrait` or `landscape`
- `pdfAllowRemoteImages` - option to enable/disable remote images in PDFs.