<?php
    include ('includes/pdf_data_injection.class.php');
    $objPDF = new PDFDataInjection();
    $objPDF->setSourcePath('./origen/');
    $objPDF->setTempPath('./temp/');
    $objPDF->setDestinationPath('./dest/');
    $objPDF->setPDF('formulario.pdf');
    $datos = [
        'campo_edad' => '2',
        'campo_comunitario' => 'comunitario',
        'campo_ciudad' => 'Madrid',
    ];
    $objPDF->setFormData($datos);

    $objPDF->createFDF();

    $objPDF->insertData();

    $objPDF->injectDataInPDF();

    $FinalPDFName = $objPDF->getFinalPDFName();

    echo $FinalPDFName;
