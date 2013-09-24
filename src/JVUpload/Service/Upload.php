<?php

namespace JVUpload\Service;

use JVEasyPhpThumbnail\Service\PHPThumb;

use Zend\Validator\File\Extension;

use Zend\Validator\File\Size;

use Zend\File\Transfer\Adapter\Http;

class Upload extends AbstractUpload
{
    /**
     * Prepara as configurações do upload
     * 
     * @return \JVUpload\Service\Upload
     */
    public function prepare()
    {
        // Organizando e preparando os tipos
        $this->prepareTypes();
        
        // verifica se o tipo de envio é imagem e se precisa gerar miniatura
        if ($this->thumbOpt !== null && $this->type != 'image')
            $this->setThumb(false);
        
        return $this;
    }
    
    /**
     * @param Http $file
     * @return \JVUpload\Service\Upload
     */
    private function generateThumbs(Http $file)
    {
        $fileInfo = $file->getFileInfo();
        
        foreach ($fileInfo as $id => $item)
        {
            // destino do arquivo
            $file->setDestination($this->thumbOpt, $id);
            
        }
        
        return $this;
    } 
    
    public function execute()
    {
        $file = new Http();
        
        $fileInfo = $file->getFileInfo();
        
        foreach ($fileInfo as $id => $item)
        {
            if (is_array($this->rename))
            {
                if (array_key_exists($id, $this->rename))
                {
                    $newName = $this->rename[$id] . "." . substr(strrchr($item['name'],'.'), 1);
                    $file->setFilters(array('Rename' => $newName), $id);
                }
            } 
            elseif ($this->rename)
            {
                $newName = md5(microtime()) . "." . substr(strrchr($item['name'],'.'), 1);
                $file->setFilters(array('Rename' => $newName));
            } else {
                $newName = $item['name'];
            }
            
            // destino do arquivo
            $destination = is_array($this->destination) ? $this->path . $this->destination[$id] : $this->path . $this->destination;
            if (!is_dir($destination))
                mkdir($destination);
            chmod($destination, "0000");
            $file->setDestination($destination, $id);
            
            // verifica se tem validação de tamanho se tiver aplica
            if ($this->sizeValidation !== null)
            {
                $size = isset($this->sizeValidation[$id]) ? $this->sizeValidation[$id] : $this->sizeValidation;
                $sizeValidation = new Size(array('min' => $size[0], 'max' => $size[1]));
                $file->setValidators(array($sizeValidation), $id);
            }
            
            // verifica se tem validação de extensão se tiver aplica
            if ($this->extValidation !== null)
            {
                $ext = isset($this->extValidation[$id]) ? $this->extValidation[$id] : $this->extValidation;
                $extValidation = new Extension($ext);
                $file->setValidators(array($extValidation), $id);
            }
            
            // valida o arquivo
            if ($file->isValid($id)) 
            {
                // envia o arquivo
                if ($file->receive($id))
                {
                    // verifica se tem destino thumb se tiver gera a thumb
                    if ($this->thumbOpt !== null)
                    {
                        // setando os opções obrigatórias
                        $arrThumbOptRequire = array('destination' => 'destination', 'width' => 'width', 'height' => 'height', 'cropimage' => 'cropimage');
                        
                        // Validando se todos os parâmetros foram passados
                        foreach ($this->thumbOpt as $thumbIndex => $thumbValue)
                            unset($arrThumbOptRequire[$thumbIndex]);
                        
                        if (count($arrThumbOptRequire)) {
                            throw new \Exception('Configure corretamente o setThumb("destination", "width", "height", "cropimage")');
                        }
                        
                        /*
                         * Criando a thumb usando o modulo JVEasyPhpThumbnail
                        */
                        //$destination = $destination . "/" . "1.jpg";
                        $phpThumb = new PHPThumb();
                        $phpThumb->Thumblocation = $this->path . $this->thumbOpt['destination'] . "/";
                        $phpThumb->Chmodlevel = '0755';
                        $phpThumb->Thumbsaveas = substr(strrchr($item['name'],'.'), 1);
                        //$phpThumb->Thumbsize = 300;
                        $phpThumb->Thumbwidth = $this->thumbOpt['width'];
                        $phpThumb->Thumbheight = $this->thumbOpt['height'];
                        $phpThumb->Cropimage = $this->thumbOpt['cropimage'];
                        
                        //$destination = $this->path . $this->thumbOpt['destination'] . "/" . $newName;
                        $phpThumb->Createthumb($destination . "/" . $newName, 'file');
                        
                    }
                    
                } 
            } else {
                echo "<pre>";
                exit(print_r($file->getMessages()));
                echo "</pre>";
            }
        }
        
        
        
        // verifica se precisa dar um rename no arquivo
        
        echo "<pre>";
        exit(print_r($file->getFilters()));
        echo "</pre>";
    }
}