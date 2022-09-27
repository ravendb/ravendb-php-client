<?php

namespace tests\RavenDB\Test\Issues\RavenDB_9745Test;

use tests\RavenDB\RemoteTestBase;

class RavenDB_9745Test extends RemoteTestBase
{
//    public void explain() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            new Companies_ByName().execute(store);
//
//            try (IDocumentSession session = store.openSession()) {
//                Company company1 = new Company();
//                company1.setName("Micro");
//
//                Company company2 = new Company();
//                company2.setName("Microsoft");
//
//                Company company3 = new Company();
//                company3.setName("Google");
//
//                session.store(company1);
//                session.store(company2);
//                session.store(company3);
//
//                session.saveChanges();
//            }
//
//            waitForIndexing(store);
//
//            try (IDocumentSession session = store.openSession()) {
//                Reference<Explanations> explanationsReference = new Reference<>();
//                List<Company> companies = session
//                        .advanced()
//                        .documentQuery(Company.class)
//                        .includeExplanations(explanationsReference)
//                        .search("name", "Micro*")
//                        .toList();
//
//                assertThat(companies)
//                        .hasSize(2);
//
//                String[] exp = explanationsReference.value.getExplanations(companies.get(0).getId());
//                assertThat(exp)
//                        .isNotNull();
//
//                exp = explanationsReference.value.getExplanations(companies.get(1).getId());
//                assertThat(exp)
//                        .isNotNull();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//
//                ExplanationOptions options = new ExplanationOptions();
//                options.setGroupKey("key");
//
//                Reference<Explanations> explanationsReference = new Reference<>();
//
//                List<Companies_ByName.Result> results = session
//                        .advanced()
//                        .documentQuery(Companies_ByName.Result.class, Companies_ByName.class)
//                        .includeExplanations(options, explanationsReference)
//                        .toList();
//
//                assertThat(results)
//                        .hasSize(3);
//
//                String[] exp = explanationsReference.value.getExplanations(results.get(0).getKey());
//                assertThat(exp)
//                        .isNotNull();
//
//                exp = explanationsReference.value.getExplanations(results.get(1).getKey());
//                assertThat(exp)
//                        .isNotNull();
//
//                exp = explanationsReference.value.getExplanations(results.get(2).getKey());
//                assertThat(exp)
//                        .isNotNull();
//            }
//        }
//    }
}
