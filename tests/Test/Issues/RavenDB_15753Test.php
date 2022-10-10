<?php

namespace tests\RavenDB\Test\Issues;

use Exception;
use RavenDB\Documents\Indexes\AdditionalAssembly;
use RavenDB\Documents\Indexes\AdditionalAssemblySet;
use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Operations\Indexes\PutIndexesOperation;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class RavenDB_15753Test extends RemoteTestBase
{
    /** @doesNotPerformAssertions */
    public function testAdditionalAssemblies_Runtime(): void
    {
        $store = $this->getDocumentStore();
        try {
            $indexDefinition = new IndexDefinition();
            $indexDefinition->setName("XmlIndex");
            $indexDefinition->setMaps(["from c in docs.Companies select new { Name = typeof(System.Xml.XmlNode).Name }"]);

            $assemblies = new AdditionalAssemblySet();

            $assemblies->append(AdditionalAssembly::fromRuntime("System.Xml"));
            $assemblies->append(AdditionalAssembly::fromRuntime("System.Xml.ReaderWriter"));
            $assemblies->append(AdditionalAssembly::fromRuntime("System.Private.Xml"));

            $indexDefinition->setAdditionalAssemblies($assemblies);

            $store->maintenance()->send(new PutIndexesOperation($indexDefinition));
        } finally {
            $store->close();
        }
    }

    public function testAdditionalAssemblies_Runtime_InvalidName(): void
    {
        $store = $this->getDocumentStore();
        try {
            try {
                $indexDefinition = new IndexDefinition();
                $indexDefinition->setName("XmlIndex");
                $indexDefinition->setMaps(["from c in docs.Companies select new { Name = typeof(System.Xml.XmlNode).Name }"]);
                $indexDefinition->setAdditionalAssemblies([AdditionalAssembly::fromRuntime("Some.Assembly.That.Does.Not.Exist")]);

                $store->maintenance()->send(new PutIndexesOperation($indexDefinition));

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertStringContainsString("Cannot load assembly 'Some.Assembly.That.Does.Not.Exist'", $exception->getMessage());
                $this->assertStringContainsString("Could not load file or assembly 'Some.Assembly.That.Does.Not.Exist", $exception->getMessage());
            }
        } finally {
            $store->close();
        }
    }

    /** @doesNotPerformAssertions */
    public function testAdditionalAssemblies_NuGet(): void
    {
        $store = $this->getDocumentStore();
        try {
            $indexDefinition = new IndexDefinition();
            $indexDefinition->setName("XmlIndex");
            $indexDefinition->setMaps(["from c in docs.Companies select new { Name = typeof(System.Xml.XmlNode).Name }"]);

            $assemblies = new AdditionalAssemblySet();
            $assemblies->append(AdditionalAssembly::fromRuntime("System.Private.Xml"));
            $assemblies->append(AdditionalAssembly::fromNuGet("System.Xml.ReaderWriter", "4.3.1"));
            $indexDefinition->setAdditionalAssemblies($assemblies);

            $store->maintenance()->send(new PutIndexesOperation($indexDefinition));
        } finally {
            $store->close();
        }
    }

    public function testAdditionalAssemblies_NuGet_InvalidName(): void
    {
        $store = $this->getDocumentStore();
        try {
            try {
                $indexDefinition = new IndexDefinition();
                $indexDefinition->setName("XmlIndex");
                $indexDefinition->setMaps(["from c in docs.Companies select new { Name = typeof(System.Xml.XmlNode).Name }"]);
                $indexDefinition->setAdditionalAssemblies([AdditionalAssembly::fromNuGet("Some.Assembly.That.Does.Not.Exist", "4.3.1")]);

                $store->maintenance()->send(new PutIndexesOperation($indexDefinition));

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertStringContainsString("Cannot load NuGet package 'Some.Assembly.That.Does.Not.Exist'", $exception->getMessage());
                $this->assertStringContainsString("NuGet package 'Some.Assembly.That.Does.Not.Exist' version '4.3.1' from 'https://api.nuget.org/v3/index.json' does not exist", $exception->getMessage());
            }
        } finally {
            $store->close();
        }
    }

    public function testAdditionalAssemblies_NuGet_InvalidSource(): void
    {
        $store = $this->getDocumentStore();
        try {
            try {
                $indexDefinition = new IndexDefinition();
                $indexDefinition->setName("XmlIndex");
                $indexDefinition->setMaps(["from c in docs.Companies select new { Name = typeof(System.Xml.XmlNode).Name }"]);
                $indexDefinition->setAdditionalAssemblies([AdditionalAssembly::fromNuGet("System.Xml.ReaderWriter", "4.3.1", "http://some.url.that.does.not.exist.com")]);

                $store->maintenance()->send(new PutIndexesOperation($indexDefinition));

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertStringContainsString("Cannot load NuGet package 'System.Xml.ReaderWriter' version '4.3.1' from 'http://some.url.that.does.not.exist.com'", $exception->getMessage());
                $this->assertStringContainsString("Unable to load the service index for source", $exception->getMessage());
            }
        } finally {
            $store->close();
        }
    }
}
