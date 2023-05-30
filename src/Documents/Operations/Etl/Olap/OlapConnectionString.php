<?php

namespace RavenDB\Documents\Operations\Etl\Olap;

use RavenDB\Documents\Operations\ConnectionStrings\ConnectionString;
use RavenDB\ServerWide\ConnectionStringType;
use Symfony\Component\Serializer\Annotation\SerializedName;

class OlapConnectionString extends ConnectionString
{
    #[SerializedName("Type")]
    private ?ConnectionStringType $type = null;

    public function __construct()
    {
        $this->type = ConnectionStringType::olap();
    }

    public function getType(): ConnectionStringType
    {
        return $this->type;
    }

//    private ?LocalSettings $localSettings = null;
//    private ?S3Settings $s3Settings = null;
//    private ?AzureSettings $azureSettings = null;
//    private ?GlacierSettings $glacierSettings = null;
//    private ?GoogleCloudSettings $googleCloudSettings = null;
//    private ?FtpSettings $ftpSettings = null;
//
//    public function getLocalSettings(): ?LocalSettings
//    {
//        return $this->localSettings;
//    }
//
//    public function setLocalSettings(?LocalSettings $localSettings): void
//    {
//        $this->localSettings = $localSettings;
//    }
//
//    public function getS3Settings(): ?S3Settings
//    {
//        return $this->s3Settings;
//    }
//
//    public function setS3Settings(?S3Settings $s3Settings): void
//    {
//        $this->s3Settings = $s3Settings;
//    }
//
//    public function getAzureSettings(): ?AzureSettings
//    {
//        return $this->azureSettings;
//    }
//
//    public function setAzureSettings(?AzureSettings $azureSettings): void
//    {
//        $this->azureSettings = $azureSettings;
//    }
//
//    public function getGlacierSettings(): ?GlacierSettings
//    {
//        return $this->glacierSettings;
//    }
//
//    public function setGlacierSettings(?GlacierSettings $glacierSettings): void
//    {
//        $this->glacierSettings = $glacierSettings;
//    }
//
//    public function getGoogleCloudSettings(): ?GoogleCloudSettings
//    {
//        return $this->googleCloudSettings;
//    }
//
//    public function setGoogleCloudSettings(?GoogleCloudSettings $googleCloudSettings): void
//    {
//        $this->googleCloudSettings = $googleCloudSettings;
//    }
//
//    public function getFtpSettings(): ?FtpSettings
//    {
//        return $this->ftpSettings;
//    }
//
//    public function setFtpSettings(?FtpSettings $ftpSettings): void
//    {
//        $this->ftpSettings = $ftpSettings;
//    }
}
