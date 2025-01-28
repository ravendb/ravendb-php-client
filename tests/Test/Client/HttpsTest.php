<?php

namespace tests\RavenDB\Test\Client;

use RavenDB\Auth\AuthOptions;
use RavenDB\Documents\DocumentStore;
use RavenDB\Exceptions\Security\AuthorizationException;
use RavenDB\ServerWide\Operations\Certificates\CertificateDefinition;
use RavenDB\ServerWide\Operations\Certificates\CertificateDefinitionArray;
use RavenDB\ServerWide\Operations\Certificates\CertificateMetadata;
use RavenDB\ServerWide\Operations\Certificates\CertificateMetadataArray;
use RavenDB\ServerWide\Operations\Certificates\CertificateRawData;
use RavenDB\ServerWide\Operations\Certificates\CreateClientCertificateOperation;
use RavenDB\ServerWide\Operations\Certificates\DatabaseAccess;
use RavenDB\ServerWide\Operations\Certificates\DatabaseAccessArray;
use RavenDB\ServerWide\Operations\Certificates\DeleteCertificateOperation;
use RavenDB\ServerWide\Operations\Certificates\EditClientCertificateOperation;
use RavenDB\ServerWide\Operations\Certificates\EditClientCertificateParameters;
use RavenDB\ServerWide\Operations\Certificates\GetCertificateMetadataOperation;
use RavenDB\ServerWide\Operations\Certificates\GetCertificateOperation;
use RavenDB\ServerWide\Operations\Certificates\GetCertificatesMetadataOperation;
use RavenDB\ServerWide\Operations\Certificates\GetCertificatesOperation;
use RavenDB\ServerWide\Operations\Certificates\PutClientCertificateOperation;
use RavenDB\ServerWide\Operations\Certificates\ReplaceClusterCertificateOperation;
use RavenDB\ServerWide\Operations\Certificates\SecurityClearance;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class HttpsTest extends RemoteTestBase
{

    public function testCanConnectWithCertificate(): void
    {
        $store = $this->getSecuredDocumentStore();
        try {
            $this->assertEquals('https', substr($store->getUrls()->offsetGet(0), 0, 5));
            $newSession = $store->openSession();
            try {
                $user1 = new User();
                $user1->setLastName("user1");
                $newSession->store($user1, "users/1");
                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }


    public function testCanReplaceCertificate(): void
    {
        $store = $this->getSecuredDocumentStore();
        try {
            // we are sending some garbage as we don't want to modify shared server certificate!
            $this->expectExceptionMessage('Unable to load the provided certificate.');
            $store->maintenance()->server()->send(new ReplaceClusterCertificateOperation("1234", true));
        } finally {
            $store->close();
        }
    }

    public function testCanCrudCertificates(): void
    {
        $store = $this->getSecuredDocumentStore();
        try {
            $cert1Thumbprint = null;
            $cert2Thumbprint = null;

            try {
                // create cert1
                /** @var ?CertificateRawData $cert1 */
                $cert1 = $store->maintenance()->server()->send(
                    new CreateClientCertificateOperation("cert1", new DatabaseAccessArray(), SecurityClearance::operator())
                );

                $this->assertNotNull($cert1);
                $this->assertNotNull($cert1->getRawData());

                $clearance = new DatabaseAccessArray();
                $clearance->offsetSet($store->getDatabase(), DatabaseAccess::readWrite());

                /** @var ?CertificateRawData $cert2 */
                $cert2 = $store->maintenance()->server()->send(
                    new CreateClientCertificateOperation("cert2", $clearance, SecurityClearance::validUser())
                );

                // create cert2
                $this->assertNotNull($cert2);
                $this->assertNotNull($cert2->getRawData());

                // list certs
                /** @var CertificateDefinitionArray $certificateDefinitions */
                $certificateDefinitions = $store->maintenance()->server()->send(
                        new GetCertificatesOperation(0, 20)
                );

                $this->assertGreaterThanOrEqual(2, count($certificateDefinitions));

                $cert1CertificateDefinitions = array_values(array_filter($certificateDefinitions->getArrayCopy(), function ($value) {
                    return $value->getName() == 'cert1';
                }));
                $cert2CertificateDefinitions = array_values(array_filter($certificateDefinitions->getArrayCopy(), function ($value) {
                    return $value->getName() == 'cert2';
                }));

                $this->assertGreaterThanOrEqual(1, count($cert1CertificateDefinitions));
                $this->assertGreaterThanOrEqual(1, count($cert2CertificateDefinitions));

                $cert1Thumbprint = $cert1CertificateDefinitions[0]->getThumbprint();
                $cert2Thumbprint = $cert2CertificateDefinitions[0]->getThumbprint();

                // delete cert1
                $store->maintenance()->server()->send(new DeleteCertificateOperation($cert1Thumbprint));

                // get cert by thumbprint
                /** @var CertificateDefinition $definition */
                $definition = $store->maintenance()->server()->send(new GetCertificateOperation($cert1Thumbprint));
                $this->assertNull($definition);

                /** @var CertificateDefinition $definition2 */
                $definition2 = $store->maintenance()->server()->send(new GetCertificateOperation($cert2Thumbprint));
                $this->assertNotNull($definition2);
                $this->assertEquals('cert2', $definition2->getName());

                // list again
                $certificateDefinitions = $store->maintenance()->server()->send(new GetCertificatesOperation(0, 20));

                $certNames = array_map(function ($value) {
                    return $value->getName();
                }, $certificateDefinitions->getArrayCopy());

                $this->assertContains('cert2', $certNames);
                $this->assertNotContains('cert1', $certNames);

                // extract public key from generated private key
                $publicKey = $this->extractCertificate($cert1);

                // put cert1 again, using put certificate command
                $putOperation = new PutClientCertificateOperation("cert3", $publicKey, new DatabaseAccessArray(), SecurityClearance::clusterAdmin());
                $store->maintenance()->server()->send($putOperation);

                $certificateDefinitions = $store->maintenance()->server()->send(new GetCertificatesOperation(0, 20));
                $certNames = array_map(function ($value) {
                    return $value->getName();
                }, $certificateDefinitions->getArrayCopy());

                $this->assertContains('cert2', $certNames);
                $this->assertNotContains('cert1', $certNames);
                $this->assertContains('cert3', $certNames);

                // and try to use edit
                $parameters = new EditClientCertificateParameters();
                $parameters->setName("cert3-newName");
                $parameters->setThumbprint($cert1Thumbprint);
                $parameters->setPermissions(new DatabaseAccessArray());
                $parameters->setClearance(SecurityClearance::validUser());

                $store->maintenance()->server()->send(new EditClientCertificateOperation($parameters));
                $certificateDefinitions = $store->maintenance()->server()->send(new GetCertificatesOperation(0, 20));
                $certNames = array_map(function ($value) {
                    return $value->getName();
                }, $certificateDefinitions->getArrayCopy());

                $this->assertContains('cert3-newName', $certNames);
                $this->assertNotContains('cert3', $certNames);

                /** @var CertificateMetadata $certificateMetadata */
                $certificateMetadata = $store->maintenance()->server()->send(new GetCertificateMetadataOperation($cert1Thumbprint));
                $this->assertNotNull($certificateMetadata);
                $this->assertEquals(SecurityClearance::validUser()->getValue(), $certificateMetadata->getSecurityClearance()->getValue());

                /** @var CertificateMetadataArray $certificatesMetadata */
                $certificatesMetadata = $store->maintenance()->server()->send(new GetCertificatesMetadataOperation($certificateMetadata->getName()));
                $this->assertCount(1, $certificatesMetadata);
                $this->assertNotNull($certificatesMetadata[0]);
                $this->assertEquals(SecurityClearance::validUser()->getValue(), $certificatesMetadata[0]->getSecurityClearance()->getValue());
            } finally {
                // try to clean up
                if ($cert1Thumbprint != null) {
                    $store->maintenance()->server()->send(new DeleteCertificateOperation($cert1Thumbprint));
                }
                if ($cert2Thumbprint != null) {
                    $store->maintenance()->server()->send(new DeleteCertificateOperation($cert2Thumbprint));
                }
            }

        } finally {
            $store->close();
        }
    }

    private function extractCertificate(CertificateRawData $certificateRawData): string
    {
        $certificatePath = tempnam(sys_get_temp_dir(), md5(uniqid(microtime(true))) . '.crt');
        try {
            $certificateRawData->extractCertificateToPem($certificatePath);
            $certificate = file_get_contents($certificatePath);
        } finally {
            unlink($certificatePath);
        }

        return base64_encode($certificate);
    }

    public function testShouldThrowAuthorizationExceptionWhenNotAuthorized() {
        $store = $this->getSecuredDocumentStore();
        try {
            $permissions = new DatabaseAccessArray();
            $permissions->offsetSet('db1', DatabaseAccess::readWrite());

            /** @var CertificateRawData  $certificateRawData */
            $certificateRawData = $store->maintenance()->server()->send(
                new CreateClientCertificateOperation("user-auth-test", $permissions, SecurityClearance::validUser())
            );

            $certificatePath = tempnam(sys_get_temp_dir(), md5(uniqid(microtime(true))) . '.crt');
            $certificateRawData->extractCertificateToPem($certificatePath);

            $storeWithOutCert = new DocumentStore($store->getUrls(), $store->getDatabase());

            try {
                // using this certificate user won't have an access to current db
                $storeWithOutCert->setAuthOptions(AuthOptions::pem(
                    $certificatePath,
                    null,
                    $store->getAuthOptions()->getCaFile() ?? $store->getAuthOptions()->getCaPath()
                ));
                $storeWithOutCert->initialize();

                $session = $storeWithOutCert->openSession();
                try {
                    $this->expectException(AuthorizationException::class);
                    $this->expectExceptionMessage('Forbidden access to ');

                    $user1 = $session->load(User::class, "users/1");

                } finally {
                    $session->close();
                }
            } finally {
                $storeWithOutCert->close();
                unlink($certificatePath);
            }
        } finally {
            $store->close();
        }
    }

    public function testCanUseServerGeneratedCertificate(): void
    {
        $store = $this->getSecuredDocumentStore();

        try {
            /** @var CertificateRawData $certificateRawData */
            $certificateRawData = $store->maintenance()->server()->send(
                    new CreateClientCertificateOperation("user-auth-test", new DatabaseAccessArray(), SecurityClearance::operator())
            );

            $certificatePath = tempnam(sys_get_temp_dir(), md5(uniqid(microtime(true))) . '.crt');
            $certificateRawData->extractCertificateToPem($certificatePath);

            $storeWithOutCert = new DocumentStore($store->getUrls(), $store->getDatabase());

            try {
                $authOptions = AuthOptions::pem($certificatePath);

                $storeWithOutCert->setAuthOptions($authOptions); // using this certificate user won't have an access to current db
                $storeWithOutCert->initialize();

                $session = $storeWithOutCert->openSession();
                try {
                    $user = $session->load(User::class, "users/1");
                    $this->assertNull($user);
                } finally {
                    $session->close();
                }

            } finally {
                unlink($certificatePath);
                $storeWithOutCert->close();
            }
        } finally {
            $store->close();
        }
    }
}
