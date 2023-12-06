<?php

namespace RavenDB\ServerWide;

use RavenDB\Documents\Indexes\AutoIndexDefinition;
use RavenDB\Documents\Indexes\AutoIndexDefinitionMap;
use RavenDB\Documents\Indexes\RollingIndex;
use RavenDB\Documents\Indexes\RollingIndexArray;
use RavenDB\Documents\Operations\Revisions\RevisionsCollectionConfiguration;
use RavenDB\Documents\Operations\Revisions\RevisionsConfiguration;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesConfiguration;

use Symfony\Component\Serializer\Annotation\SerializedName;

// @todo: Add serialized names to properties

// !status: IN PROGRESS
class DatabaseRecord
{
    /** @SerializedName("DatabaseName") */
    private string $databaseName = '';

    /** @SerializedName("Disabled") */
    private bool $disabled = false;

    /** @SerializedName("Encrypted") */
    private bool $encrypted = false;
    private int $etagForBackup = 0;

    /** @SerializedName("DeletionInProgress") */
    private ?DeletionInProgressStatusArray $deletionInProgress = null;

    /** @SerializedName("RollingIndexes") */
    private RollingIndexArray $rollingIndexes;

    /** @SerializedName("DatabaseState") */
    private DatabaseStateStatus $databaseState;

    /** @SerializedName("LockMode") */
    private DatabaseLockMode $lockMode;

    /** @SerializedName("Topology") */
    private ?DatabaseTopology $topology = null;

    private ConflictSolver $conflictSolverConfig;
    private DocumentsCompressionConfiguration $documentsCompression;
//    private Map<String, SorterDefinition> sorters = new HashMap<>();
//    private Map<String, AnalyzerDefinition> analyzers = new HashMap<>();
//    private Map<String, IndexDefinition> indexes;
//    private Map<String, List<IndexHistoryEntry>> indexesHistory;

    /** @SerializedName("AutoIndexes") */
    private ?AutoIndexDefinitionMap $autoIndexes =  null;
//    private Map<String, String> settings = new HashMap<>();
    private RevisionsConfiguration $revisions;
    private TimeSeriesConfiguration $timeSeries;
    private RevisionsCollectionConfiguration $revisionsForConflicts;
//    private ExpirationConfiguration expiration;
//    private RefreshConfiguration refresh;
//    private List<PeriodicBackupConfiguration> periodicBackups = new ArrayList<>();
//    private List<ExternalReplication> externalReplications = new ArrayList<>();
//    private List<PullReplicationAsSink> sinkPullReplications = new ArrayList<>();
//    private List<PullReplicationDefinition> hubPullReplications = new ArrayList<>();
//    private Map<String, RavenConnectionString> ravenConnectionStrings = new HashMap<>();
//    private Map<String, SqlConnectionString> sqlConnectionStrings = new HashMap<>();
//    private Map<String, OlapConnectionString> olapConnectionStrings = new HashMap<>();
//    private List<RavenEtlConfiguration> ravenEtls = new ArrayList<>();
//    private List<SqlEtlConfiguration> sqlEtls = new ArrayList<>();
//    private List<OlapEtlConfiguration> olapEtls = new ArrayList<>();
//    private ClientConfiguration client;
//    private StudioConfiguration studio;
//    private long truncatedClusterTransactionCommandsCount;
//    private Set<String> unusedDatabaseIds = new HashSet<>();


    public function __construct(string $databaseName = '')
    {
        $this->databaseName = $databaseName;

        $this->deletionInProgress = new DeletionInProgressStatusArray();
        $this->lockMode = DatabaseLockMode::none();
        $this->databaseState = DatabaseStateStatus::normal();

        $this->autoIndexes = new AutoIndexDefinitionMap();
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function setDatabaseName(string $databaseName): void
    {
        $this->databaseName = $databaseName;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

//    public Map<String, String> getSettings() {
//        return settings;
//    }
//
//    public void setSettings(Map<String, String> settings) {
//        this.settings = settings;
//    }
//
    public function getConflictSolverConfig(): ConflictSolver
    {
        return $this->conflictSolverConfig;
    }

    public function setConflictSolverConfig(ConflictSolver $conflictSolverConfig): void
    {
        $this->conflictSolverConfig = $conflictSolverConfig;
    }

    public function getDocumentsCompression(): DocumentsCompressionConfiguration
    {
        return $this->documentsCompression;
    }

    public function setDocumentsCompression(DocumentsCompressionConfiguration $documentsCompression): void
    {
        $this->documentsCompression = $documentsCompression;
    }

    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    public function setEncrypted(bool $encrypted): void
    {
        $this->encrypted = $encrypted;
    }

    public function getEtagForBackup(): int
    {
        return $this->etagForBackup;
    }

    public function setEtagForBackup(int $etagForBackup): void
    {
        $this->etagForBackup = $etagForBackup;
    }

    public function getDeletionInProgress(): ?DeletionInProgressStatusArray
    {
        return $this->deletionInProgress;
    }

    public function addDeletionInProgress(string $key, DeletionInProgressStatus $status): void
    {
        $this->deletionInProgress[$key] = $status;
    }

    /**
     * @param DeletionInProgressStatusArray|null|array $deletionInProgress
     */
    public function setDeletionInProgress($deletionInProgress): void
    {
        if (is_array($deletionInProgress)) {
            $deletionInProgress = DeletionInProgressStatusArray::fromArray($deletionInProgress);
        }
        $this->deletionInProgress = $deletionInProgress;
    }

    public function getRollingIndexes(): RollingIndexArray
    {
        return $this->rollingIndexes;
    }

    /**
     * @param RollingIndexArray|array|null $rollingIndexes
     */
    public function setRollingIndexes($rollingIndexes): void
    {
        if (is_array($rollingIndexes)) {
            $rollingIndexes = RollingIndexArray::fromArray($rollingIndexes);
        }
        $this->rollingIndexes = $rollingIndexes;
    }

    public function addRollingIndex(string $key, RollingIndex $rollingIndex): void
    {
        $this->rollingIndexes[$key] = $rollingIndex;
    }

    public function getTopology(): ?DatabaseTopology
    {
        return $this->topology;
    }

    public function setTopology(?DatabaseTopology $topology): void
    {
        $this->topology = $topology;
    }

//    public Map<String, SorterDefinition> getSorters() {
//        return sorters;
//    }
//
//    public void setSorters(Map<String, SorterDefinition> sorters) {
//        this.sorters = sorters;
//    }
//
//    public Map<String, AnalyzerDefinition> getAnalyzers() {
//        return analyzers;
//    }
//
//    public void setAnalyzers(Map<String, AnalyzerDefinition> analyzers) {
//        this.analyzers = analyzers;
//    }
//
//    public Map<String, IndexDefinition> getIndexes() {
//        return indexes;
//    }
//
//    public void setIndexes(Map<String, IndexDefinition> indexes) {
//        this.indexes = indexes;
//    }
//
    public function getAutoIndexes(): ?AutoIndexDefinitionMap
    {
        return $this->autoIndexes;
    }

    public function setAutoIndexes(?AutoIndexDefinitionMap $autoIndexes): void
    {
        $this->autoIndexes = $autoIndexes;
    }

    public function getRevisions(): RevisionsConfiguration
    {
        return $this->revisions;
    }

    public function setRevisions(RevisionsConfiguration $revisions): void {
        $this->revisions = $revisions;
    }

    public function getTimeSeries(): TimeSeriesConfiguration
    {
        return $this->timeSeries;
    }

    public function setTimeSeries(TimeSeriesConfiguration $timeSeries): void
    {
        $this->timeSeries = $timeSeries;
    }

//    public ExpirationConfiguration getExpiration() {
//        return expiration;
//    }
//
//    public void setExpiration(ExpirationConfiguration expiration) {
//        this.expiration = expiration;
//    }
//
//    public List<PeriodicBackupConfiguration> getPeriodicBackups() {
//        return periodicBackups;
//    }
//
//    public void setPeriodicBackups(List<PeriodicBackupConfiguration> periodicBackups) {
//        this.periodicBackups = periodicBackups;
//    }
//
//    public List<ExternalReplication> getExternalReplications() {
//        return externalReplications;
//    }
//
//    public void setExternalReplications(List<ExternalReplication> externalReplications) {
//        this.externalReplications = externalReplications;
//    }
//
//    public List<PullReplicationAsSink> getSinkPullReplications() {
//        return sinkPullReplications;
//    }
//
//    public void setSinkPullReplications(List<PullReplicationAsSink> sinkPullReplications) {
//        this.sinkPullReplications = sinkPullReplications;
//    }
//
//    public List<PullReplicationDefinition> getHubPullReplications() {
//        return hubPullReplications;
//    }
//
//    public void setHubPullReplications(List<PullReplicationDefinition> hubPullReplications) {
//        this.hubPullReplications = hubPullReplications;
//    }
//
//    public Map<String, RavenConnectionString> getRavenConnectionStrings() {
//        return ravenConnectionStrings;
//    }
//
//    public void setRavenConnectionStrings(Map<String, RavenConnectionString> ravenConnectionStrings) {
//        this.ravenConnectionStrings = ravenConnectionStrings;
//    }
//
//    public Map<String, SqlConnectionString> getSqlConnectionStrings() {
//        return sqlConnectionStrings;
//    }
//
//    public void setSqlConnectionStrings(Map<String, SqlConnectionString> sqlConnectionStrings) {
//        this.sqlConnectionStrings = sqlConnectionStrings;
//    }
//
//    public Map<String, OlapConnectionString> getOlapConnectionStrings() {
//        return olapConnectionStrings;
//    }
//
//    public void setOlapConnectionStrings(Map<String, OlapConnectionString> olapConnectionStrings) {
//        this.olapConnectionStrings = olapConnectionStrings;
//    }
//
//    public List<RavenEtlConfiguration> getRavenEtls() {
//        return ravenEtls;
//    }
//
//    public void setRavenEtls(List<RavenEtlConfiguration> ravenEtls) {
//        this.ravenEtls = ravenEtls;
//    }
//
//    public List<SqlEtlConfiguration> getSqlEtls() {
//        return sqlEtls;
//    }
//
//    public void setSqlEtls(List<SqlEtlConfiguration> sqlEtls) {
//        this.sqlEtls = sqlEtls;
//    }
//
//    public List<OlapEtlConfiguration> getOlapEtls() {
//        return olapEtls;
//    }
//
//    public void setOlapEtls(List<OlapEtlConfiguration> olapEtls) {
//        this.olapEtls = olapEtls;
//    }
//
//    public ClientConfiguration getClient() {
//        return client;
//    }
//
//    public void setClient(ClientConfiguration client) {
//        this.client = client;
//    }
//
//    public StudioConfiguration getStudio() {
//        return studio;
//    }
//
//    public void setStudio(StudioConfiguration studio) {
//        this.studio = studio;
//    }
//
//    public long getTruncatedClusterTransactionCommandsCount() {
//        return truncatedClusterTransactionCommandsCount;
//    }
//
//    public void setTruncatedClusterTransactionCommandsCount(long truncatedClusterTransactionCommandsCount) {
//        this.truncatedClusterTransactionCommandsCount = truncatedClusterTransactionCommandsCount;
//    }

    public function getDatabaseState(): DatabaseStateStatus
    {
        return $this->databaseState;
    }

    public function setDatabaseState(DatabaseStateStatus $databaseState): void
    {
        $this->databaseState = $databaseState;
    }

    public function getLockMode(): DatabaseLockMode
    {
        return $this->lockMode;
    }

    public function setLockMode(DatabaseLockMode $lockMode): void
    {
        $this->lockMode = $lockMode;
    }

//    public Map<String, List<IndexHistoryEntry>> getIndexesHistory() {
//        return indexesHistory;
//    }
//
//    public void setIndexesHistory(Map<String, List<IndexHistoryEntry>> indexesHistory) {
//        this.indexesHistory = indexesHistory;
//    }

    public function getRevisionsForConflicts(): RevisionsCollectionConfiguration {
        return $this->revisionsForConflicts;
    }

    public function setRevisionsForConflicts(RevisionsCollectionConfiguration $revisionsForConflicts): void {
        $this->revisionsForConflicts = $revisionsForConflicts;
    }

//    public RefreshConfiguration getRefresh() {
//        return refresh;
//    }
//
//    public void setRefresh(RefreshConfiguration refresh) {
//        this.refresh = refresh;
//    }
//
//    public Set<String> getUnusedDatabaseIds() {
//        return unusedDatabaseIds;
//    }
//
//    public void setUnusedDatabaseIds(Set<String> unusedDatabaseIds) {
//        this.unusedDatabaseIds = unusedDatabaseIds;
//    }
//
//    public static class IndexHistoryEntry {
//        private IndexDefinition definition;
//        private String source;
//        private Date createdAt;
//        private Map<String, RollingIndexDeployment> rollingDeployment;
//
//        public IndexDefinition getDefinition() {
//            return definition;
//        }
//
//        public void setDefinition(IndexDefinition definition) {
//            this.definition = definition;
//        }
//
//        public String getSource() {
//            return source;
//        }
//
//        public void setSource(String source) {
//            this.source = source;
//        }
//
//        public Date getCreatedAt() {
//            return createdAt;
//        }
//
//        public void setCreatedAt(Date createdAt) {
//            this.createdAt = createdAt;
//        }
//
//        public Map<String, RollingIndexDeployment> getRollingDeployment() {
//            return rollingDeployment;
//        }
//
//        public void setRollingDeployment(Map<String, RollingIndexDeployment> rollingDeployment) {
//            this.rollingDeployment = rollingDeployment;
//        }
//    }
}
