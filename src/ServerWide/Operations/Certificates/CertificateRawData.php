<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Http\ResultInterface;
use ZipArchive;

// !status: DONE
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

    public function extractCertificateToFile(string $certFilePath, string $certType = 'crt')
    {
        $tmpZipPath = tempnam(sys_get_temp_dir(), md5(uniqid(microtime(true))));

        try {
            file_put_contents($tmpZipPath, $this->rawData);

            $zip = new ZipArchive();
            if (true === $zip->open($tmpZipPath)) {

                $certFileName = '';

                for( $i = 0; $i < $zip->numFiles; $i++ ){
                    $stat = $zip->statIndex( $i );
                    if (str_ends_with($stat['name'], '.' . $certType)) {
                        $certFileName = $stat['name'];
                    }
                }

                if (empty($certFileName)) {
                    throw new \Exception('Adequate file can not be found in downloaded certificate.');
                }

                if($certData = $zip->getFromName($certFileName)) {
                    file_put_contents($certFilePath, $certData);
                }
            }
        } finally {
            unlink($tmpZipPath);
        }
    }
}
