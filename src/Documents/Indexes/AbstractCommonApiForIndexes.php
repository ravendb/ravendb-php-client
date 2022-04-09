<?php

namespace RavenDB\Documents\Indexes;

class AbstractCommonApiForIndexes
{
//    private Map<String, String> additionalSources;
//    private Set<AdditionalAssembly> additionalAssemblies;
//    private IndexConfiguration configuration;
//
//    protected AbstractCommonApiForIndexes() {
//        configuration = new IndexConfiguration();
//    }

    /**
     * Gets a value indicating whether this instance is map reduce index definition
     *
     * @return bool true if index is map reduce
     */
    public function isMapReduce(): bool
    {
        return false;
    }

    /**
     * Generates index name from type name replacing all _ with /
     *
     * @return string index name
     */
    public function getIndexName(): string
    {
        //@todo: implement this method
        return 'simpleClassName'; //getClass().getSimpleName().replaceAll("_", "/");
    }

//    public Map<String, String> getAdditionalSources() {
//        return additionalSources;
//    }
//
//    public void setAdditionalSources(Map<String, String> additionalSources) {
//        this.additionalSources = additionalSources;
//    }
//
//    public Set<AdditionalAssembly> getAdditionalAssemblies() {
//        return additionalAssemblies;
//    }
//
//    public void setAdditionalAssemblies(Set<AdditionalAssembly> additionalAssemblies) {
//        this.additionalAssemblies = additionalAssemblies;
//    }
//
//    public IndexConfiguration getConfiguration() {
//        return configuration;
//    }
//
//    public void setConfiguration(IndexConfiguration configuration) {
//        this.configuration = configuration;
//    }
}
