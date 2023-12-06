<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Http\ResultInterface;
use ZipArchive;

class CertificateRawData implements ResultInterface
{
    private $rawData = null;

    public function getRawData()
    {
        return $this->rawData;
    }

    public function setRawData($rawData): void
    {
        $this->rawData = $rawData;
    }

    public function extractCertificateToPem(string $certFilePath)
    {
        $tmpZipPath = tempnam(sys_get_temp_dir(), md5(uniqid(microtime(true))));

        try {
            file_put_contents($tmpZipPath, $this->rawData);

            $zip = new ZipArchive();
            if (true === $zip->open($tmpZipPath)) {

                $certFileName = '';
                $keyFileName = '';

                for( $i = 0; $i < $zip->numFiles; $i++ ){
                    $stat = $zip->statIndex( $i );
                    if (str_ends_with($stat['name'], '.crt')) {
                        $certFileName = $stat['name'];
                    }
                    if (str_ends_with($stat['name'], '.key')) {
                        $keyFileName = $stat['name'];
                    }
                }

                if (empty($certFileName) || empty($keyFileName)) {
                    throw new \Exception('Adequate file can not be found in downloaded certificate.');
                }

                $certData = $zip->getFromName($certFileName);
                $certData .= PHP_EOL;
                $certData .= $zip->getFromName($keyFileName);

                if(!empty($certData)) {
                    file_put_contents($certFilePath, $certData);
                }
            }
        } finally {
            unlink($tmpZipPath);
        }
    }
}
