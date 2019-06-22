<?php
    class PDFDataInjection {
        private static $SO;
        private $itemString = '';
        private $rutaDeOrigen = './';
        private $rutaDeTemporales = './';
        private $rutaDeDestino = './';
        private $pdfOriginal = '';
        private $fdfObtenido = '';
        private $pdfFinal = '';
        private $errorDeFichero = false;
        private $datosDeFormulario = [];

        public function __construct()
        {
            $sistema = php_uname();
            if (stripos($sistema, "Linux") !== false) {
                self::$SO = 'L'; // Linux
            } elseif (stripos($sistema, "Windows") !== false) {
                self::$SO = 'W'; // Windows
            } else {
                self::$SO = 'U'; // Undetermined
                die("EL S.O. ES DESCONOCIDO. NO SE PUEDE USAR ESTE PROCESO.");
            }
            $this->itemString = md5(uniqid());
            $this->fdfObtenido = "temporal_".$this->itemString.".fdf";
            $this->pdfFinal = "form_destino_".$this->itemString.".pdf";
        }

        public function setSourcePath($ruta)
        {
            $this->rutaDeOrigen = $ruta;
        }

        public function setTempPath($ruta)
        {
            $this->rutaDeTemporales = $ruta;
        }

        public function setDestinationPath($ruta)
        {
            $this->rutaDeDestino = $ruta;
        }

        public function setPDF($pdf)
        {
            $this->pdfOriginal = $this->rutaDeOrigen.$pdf;
            $fileType = mime_content_type ($this->pdfOriginal);
            $esPDF = strpos($fileType, 'application/pdf');
            if ($esPDF === false) {
                $this->errorDeFichero = true;
            }
        }

        public function setFormData($datos)
        {
            if ($this->errorDeFichero) return;
            $this->datosDeFormulario = $datos;
        }

        public function createFDF()
        {
            if ($this->errorDeFichero) return;
            $instruccion = "";
            if (self::$SO == 'W') {
                $instruccion .= 'pdftk '.$this->pdfOriginal.' generate_fdf output '.$this->rutaDeTemporales.$this->fdfObtenido;
            } else { // El sistema es Linux
                $instruccion .= '/usr/bin/pdftk '.$this->pdfOriginal.' generate_fdf output '.$this->rutaDeTemporales.$this->fdfObtenido;
            }
            passthru($instruccion);
        }

        public function insertData()
        {
            if ($this->errorDeFichero) return;
            $contenidoDeFDF = file_get_contents($this->rutaDeTemporales.$this->fdfObtenido);
            foreach ($this->datosDeFormulario as $keyData=>$data)
            {
                $posicionDeNombreDeDato = strpos($contenidoDeFDF, '/T ('.$keyData.')');
                /**
                 * Extraemos una cadena temporal que contiene desde el principio del fichero FDF 
                 * hasta el inicio del nombre de la variable que vamos a modificar.
                 * También determinamos el resto del fichero.
                 */
                $cadenaTemporal = substr($contenidoDeFDF, 0, $posicionDeNombreDeDato);
                $restoDelFichero = substr($contenidoDeFDF, $posicionDeNombreDeDato);
                /**
                 * Determinamos la posición de inicio del valor, y obtenemos el valor que hay.
                 */
                $posicionDeInicioDeValor = strrpos($cadenaTemporal, '/V ') + 3;
                $valorActual = substr($cadenaTemporal, $posicionDeInicioDeValor, $posicionDeNombreDeDato - $posicionDeInicioDeValor);
                /**
                 * Determinamos si el valor es de tipo paréntesis, es decir, 
                 * una cadena de texto o un valor de selector desplegable.
                 * La alternativa es que sea de tipo slash. Esto indica un valor 
                 * de un botón de radio, o un checkbox, que puede ser el 
                 * nombre declarado u Off (o, simplemente, nada).
                 */
                $tipoDeValor = (substr($valorActual, 0, 1) == '(') ? 'P' : 'S'; // Paréntesis o Slash
                /**
                 * Determinamos el nuevo valor con el deseado, 
                 * según sea de paréntesis o de slash.
                 */
                if ($tipoDeValor == 'P') {
                    $nuevoValor = '('.$data.')';
                } else {
                    $nuevoValor = '/'.$data;
                }
                /**
                 * En la cadena temporal sustituimos la última aparición del valor original 
                 * (puede haber otras iguales más arriba, que no nos interensan) por el nuevo valor
                 */
                $cadenaTemporal = substr($cadenaTemporal, 0, $posicionDeInicioDeValor).$nuevoValor."\n";
                /**
                 * Unimos la cadena temporal con el resto del fichero.
                 */
                $contenidoDeFDF = $cadenaTemporal.$restoDelFichero;
            }
            file_put_contents($this->rutaDeTemporales.$this->fdfObtenido, $contenidoDeFDF);
        }

        public function injectDataInPDF()
        {
            if ($this->errorDeFichero) return;
            $instruccion = "";
            if (self::$SO == 'W') {
                $instruccion .= 'pdftk '.$this->pdfOriginal.' fill_form '.$this->rutaDeTemporales.$this->fdfObtenido.' output '.$this->rutaDeDestino.$this->pdfFinal;
            } else { // El sistema es Linux
                $instruccion .= '/usr/bin/pdftk '.$this->pdfOriginal.' fill_form '.$this->rutaDeTemporales.$this->fdfObtenido.' output '.$this->rutaDeDestino.$this->pdfFinal;
            }
            passthru($instruccion);
            unlink($this->rutaDeTemporales.$this->fdfObtenido);
        }

        public function getFinalPDFName()
        {
            return $this->pdfFinal;
        }
    }
?>
