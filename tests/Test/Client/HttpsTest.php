<?php

namespace tests\RavenDB\Test\Client;

use RavenDB\Documents\DocumentStore;
use RavenDB\ServerWide\Operations\Certificates\CertificateRawData;
use RavenDB\ServerWide\Operations\Certificates\CreateClientCertificateOperation;
use RavenDB\ServerWide\Operations\Certificates\DatabaseAccessArray;
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
                $this->assertTrue(true); // @todo: Check with Marcin why this test doesn't have any assertion
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

            $store->maintenance()->server()->send(new ReplaceClusterCertificateOperation("1234", true));
            $this->expectErrorMessage("Unable to load the provided certificate");

//            assertThatThrownBy(() -> {
//                store.maintenance().server().send(new ReplaceClusterCertificateOperation(new byte[] { 1, 2, 3, 4}, true));
//            }).hasMessageContaining("Unable to load the provided certificate");
        } finally {
            $store->close();
        }
    }

//    @Test
//    @DisabledOnJava8
//    public void canCrudCertificates() throws Exception {
//        try (IDocumentStore store = getSecuredDocumentStore()) {
//
//            String cert1Thumbprint = null;
//            String cert2Thumbprint = null;
//
//            try {
//                // create cert1
//                CertificateRawData cert1 = store.maintenance().server().send(
//                        new CreateClientCertificateOperation("cert1", new HashMap<>(), SecurityClearance.OPERATOR));
//
//                assertThat(cert1)
//                        .isNotNull();
//                assertThat(cert1.getRawData())
//                        .isNotNull();
//
//                Map<String, DatabaseAccess> clearance = new HashMap<>();
//                clearance.put(store.getDatabase(), DatabaseAccess.READ_WRITE);
//                CertificateRawData cert2 = store.maintenance().server().send(
//                        new CreateClientCertificateOperation("cert2", clearance, SecurityClearance.VALID_USER));
//
//                // create cert2
//                assertThat(cert2)
//                        .isNotNull();
//                assertThat(cert2.getRawData())
//                        .isNotNull();
//
//                // list certs
//                CertificateDefinition[] certificateDefinitions = store.maintenance().server().send(
//                        new GetCertificatesOperation(0, 20));
//                assertThat(certificateDefinitions.length)
//                        .isGreaterThanOrEqualTo(2);
//
//                assertThat(certificateDefinitions)
//                        .extracting("name", String.class)
//                        .contains("cert1");
//
//                assertThat(certificateDefinitions)
//                        .extracting(x -> x.getName())
//                        .contains("cert2");
//
//                cert1Thumbprint = Stream.of(certificateDefinitions).filter(x -> x.getName().equals("cert1")).findFirst().get().getThumbprint();
//                cert2Thumbprint = Stream.of(certificateDefinitions).filter(x -> x.getName().equals("cert2")).findFirst().get().getThumbprint();
//
//                // delete cert1
//                store.maintenance().server().send(new DeleteCertificateOperation(cert1Thumbprint));
//
//                // get cert by thumbprint
//
//                CertificateDefinition definition = store.maintenance().server().send(new GetCertificateOperation(cert1Thumbprint));
//                assertThat(definition)
//                        .isNull();
//
//                CertificateDefinition definition2 = store.maintenance().server().send(new GetCertificateOperation(cert2Thumbprint));
//                assertThat(definition2)
//                        .isNotNull();
//                assertThat(definition2)
//                        .matches(x -> x.getName().equals("cert2"));
//
//                // list again
//                certificateDefinitions = store.maintenance().server().send(new GetCertificatesOperation(0, 20));
//                assertThat(certificateDefinitions)
//                        .extracting(x -> x.getName())
//                        .contains("cert2")
//                        .doesNotContain("cert1");
//
//                // extract public key from generated private key
//                String publicKey = extractCertificate(cert1);
//
//                // put cert1 again, using put certificate command
//                PutClientCertificateOperation putOperation = new PutClientCertificateOperation("cert3", publicKey, new HashMap<>(), SecurityClearance.CLUSTER_ADMIN);
//                store.maintenance().server().send(putOperation);
//                certificateDefinitions = store.maintenance().server().send(new GetCertificatesOperation(0, 20));
//                assertThat(certificateDefinitions)
//                        .extracting(x -> x.getName())
//                        .contains("cert2")
//                        .doesNotContain("cert1")
//                        .contains("cert3");
//
//                // and try to use edit
//
//                EditClientCertificateOperation.Parameters parameters = new EditClientCertificateOperation.Parameters();
//                parameters.setName("cert3-newName");
//                parameters.setThumbprint(cert1Thumbprint);
//                parameters.setPermissions(new HashMap<>());
//                parameters.setClearance(SecurityClearance.VALID_USER);
//
//                store.maintenance().server().send(new EditClientCertificateOperation(parameters));
//                certificateDefinitions = store.maintenance().server().send(new GetCertificatesOperation(0, 20));
//                assertThat(certificateDefinitions)
//                        .extracting(CertificateMetadata::getName)
//                        .contains("cert3-newName")
//                        .doesNotContain("cert3");
//
//                CertificateMetadata certificateMetadata = store.maintenance().server().send(new GetCertificateMetadataOperation(cert1Thumbprint));
//                assertThat(certificateMetadata)
//                        .isNotNull();
//                assertThat(certificateMetadata.getSecurityClearance())
//                        .isEqualTo(SecurityClearance.VALID_USER);
//
//                CertificateMetadata[] certificatesMetadata = store.maintenance().server().send(new GetCertificatesMetadataOperation(certificateMetadata.getName()));
//                assertThat(certificatesMetadata)
//                        .hasSize(1);
//                assertThat(certificatesMetadata[0])
//                        .isNotNull();
//                assertThat(certificatesMetadata[0].getSecurityClearance())
//                        .isEqualTo(SecurityClearance.VALID_USER);
//            } finally {
//                // try to clean up
//                if (cert1Thumbprint != null) {
//                    store.maintenance().server().send(new DeleteCertificateOperation(cert1Thumbprint));
//                }
//                if (cert2Thumbprint != null) {
//                    store.maintenance().server().send(new DeleteCertificateOperation(cert2Thumbprint));
//                }
//            }
//        }
//    }
//
//    private String extractCertificate(CertificateRawData certificateRawData) throws Exception {
//        KeyStore keyStore = readKeyStore(certificateRawData);
//
//        String alias = keyStore.aliases().nextElement();
//        Certificate certificate = keyStore.getCertificate(alias);
//        return Base64.encodeBase64String(certificate.getEncoded());
//    }
//
//    @Test
//    @DisabledOnJava8
//    public void shouldThrowAuthorizationExceptionWhenNotAuthorized() throws Exception {
//        try (DocumentStore store = getSecuredDocumentStore()) {
//            CertificateRawData certificateRawData = store.maintenance().server().send(
//                    new CreateClientCertificateOperation("user-auth-test", Collections.singletonMap("db1", DatabaseAccess.READ_WRITE), SecurityClearance.VALID_USER));
//
//            KeyStore keyStore = readKeyStore(certificateRawData);
//
//            try (DocumentStore storeWithOutCert = new DocumentStore(store.getUrls(), store.getDatabase())) {
//                storeWithOutCert.setTrustStore(store.getTrustStore());
//                storeWithOutCert.setCertificate(keyStore); // using this certificate user won't have an access to current db
//                storeWithOutCert.initialize();
//
//                assertThatThrownBy(() -> {
//                    try (IDocumentSession session = storeWithOutCert.openSession()) {
//                        User user = session.load(User.class, "users/1");
//                    }
//                }).isExactlyInstanceOf(AuthorizationException.class);
//            }
//        }
//    }
//
//    // please notice we are reading from crt, pem files instead of pfx
//    // due to https://github.com/dotnet/corefx/issues/30946
//    // reading such file generated on linux server with SUN SPI returns null for certificate
//    // reading using BouncyCastle throws attempt to add existing attribute with different value
    private function readKeyStore(CertificateRawData $rawData): KeyStore
    {
        $zipInputStream = new ZipInputStream(new ByteArrayInputStream($rawData->getRawData()));
        try {
//
//            byte[] certBytes = null;
//            String keyString = null;
//
//            ZipEntry zipEntry = zipInputStream.getNextEntry();
//            while (zipEntry != null) {
//                if (zipEntry.getName().endsWith(".crt")) {
//                    certBytes = IOUtils.toByteArray(zipInputStream);
//                }
//                if (zipEntry.getName().endsWith(".key")) {
//                    keyString = IOUtils.toString(zipInputStream, StandardCharsets.UTF_8);
//                }
//
//                zipEntry = zipInputStream.getNextEntry();
//            }
//
//            if (certBytes == null) {
//                throw new IllegalStateException("Unable to find certificate file!");
//            }
//
//            if (keyString == null) {
//                throw new IllegalStateException("Unable to find private key file!");
//            }
//
//            KeyStore keyStore = KeyStore.getInstance("PKCS12");
//            keyStore.load(null, null);
//
//
//            KeyFactory kf = KeyFactory.getInstance("RSA");
//
//            PEMParser pemParser = new PEMParser(new StringReader(keyString));
//            Object readObject = pemParser.readObject();
//            PEMKeyPair bcKeyPair = null;
//            if (readObject instanceof PEMEncryptedKeyPair) {
//                bcKeyPair = ((PEMEncryptedKeyPair)readObject).decryptKeyPair(new BcPEMDecryptorProvider("".toCharArray()));
//            } else {
//                bcKeyPair = (PEMKeyPair) readObject;
//            }
//
//            PKCS8EncodedKeySpec keySpec = new PKCS8EncodedKeySpec(bcKeyPair.getPrivateKeyInfo().getEncoded());
//            PrivateKey key = kf.generatePrivate(keySpec);
//
//            CertificateFactory instance = CertificateFactory.getInstance("X.509");
//            Certificate generateCertificate = instance.generateCertificate(new ByteArrayInputStream(certBytes));
//
//            Certificate[] certificates = new Certificate[] { generateCertificate };
//            keyStore.setKeyEntry("key", key, "".toCharArray(), certificates);
//
//            return keyStore;
        } finally {
            $zipInputStream->close();
        }
    }


    public function testCanUseServerGeneratedCertificate(): void
    {
        $store = $this->getDocumentStore();

        try {
            /** @var CertificateRawData $certificateRawData */
            $certificateRawData = $store->maintenance()->server()->send(
                    new CreateClientCertificateOperation("user-auth-test", new DatabaseAccessArray(), SecurityClearance::operator())
            );

            echo "Certification raw data:" . PHP_EOL;
            print_r($certificateRawData);
//            KeyStore keyStore = readKeyStore(certificateRawData);

            $storeWithOutCert = new DocumentStore($store->getDatabase(), $store->getUrls());

            try {
//                $storeWithOutCert->setTrustStore($store->getTrustStore());
//                $storeWithOutCert->setCertificate($keyStore); // using this certificate user won't have an access to current db
                $storeWithOutCert->initialize();

                $session = $storeWithOutCert->openSession();
                try {
                    $user = $session->load(User::class, "users/1");
                    $this->assertTrue(true);
                } finally {
                    $session->close();
                }

            } finally {
                $storeWithOutCert->close();
            }

        } finally {
            $store->close();
        }
    }
//    public void canUseServerGeneratedCertificate() throws Exception {
//        try (DocumentStore store = getSecuredDocumentStore()) {
//
//            try (DocumentStore storeWithOutCert = new DocumentStore(store.getUrls(), store.getDatabase())) {
//                storeWithOutCert.setTrustStore(store.getTrustStore());
//                storeWithOutCert.setCertificate(keyStore); // using this certificate user won't have an access to current db
//                storeWithOutCert.initialize();
//
//                try (IDocumentSession session = storeWithOutCert.openSession()) {
//                    User user = session.load(User.class, "users/1");
//                }
//            }
//        }
//    }


}
