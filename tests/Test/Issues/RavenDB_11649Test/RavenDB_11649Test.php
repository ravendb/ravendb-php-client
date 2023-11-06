<?php

namespace tests\RavenDB\Test\Issues\RavenDB_11649Test;

use tests\RavenDB\RemoteTestBase;

class RavenDB_11649Test extends RemoteTestBase
{
    public function test_whatChanged_WhenInnerPropertyChanged_ShouldReturnThePropertyNamePlusPath(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $doc = new OuterClass();
                $doc->setA("outerValue");
                $innerClass = new InnerClass();
                $doc->setInnerClass($innerClass);

                $id = "docs/1";
                $session->store($doc, $id);
                $session->saveChanges();

                $doc->getInnerClass()->setA("newInnerValue");

                // action
                $changes = $session->advanced()->whatChanged();

                // assert
                $changedPaths = array_map(function ($item) {
                    return $item->getFieldPath();
                }, $changes[$id]->getArrayCopy());

                $this->assertEquals(['innerClass'], $changedPaths);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_whatChanged_WhenInnerPropertyChangedFromNull_ShouldReturnThePropertyNamePlusPath(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                // arrange
                $doc = new OuterClass();
                $doc->setA("outerValue");

                $innerClass = new InnerClass();
                $doc->setInnerClass($innerClass);
                $innerClass->setA(null);

                $id = "docs/1";
                $session->store($doc, $id);
                $session->saveChanges();

                $doc->getInnerClass()->setA("newInnerValue");

                // action
                $changes = $session->advanced()->whatChanged();

                // assert
                $changedPaths = array_map(function ($item) {
                    return $item->getFieldPath();
                }, $changes[$id]->getArrayCopy());

                $this->assertEquals(['innerClass'], $changedPaths);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_whatChanged_WhenPropertyOfInnerPropertyChangedToNull_ShouldReturnThePropertyNamePlusPath(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                // arrange
                $doc = new OuterClass();
                $doc->setA("outerValue");
                $innerClass = new InnerClass();
                $innerClass->setA("innerValue");
                $doc->setInnerClass($innerClass);

                $id = "docs/1";
                $session->store($doc, $id);
                $session->saveChanges();

                $doc->getInnerClass()->setA(null);

                // action
                $changes = $session->advanced()->whatChanged();

                // assert
                $changedPaths = array_map(function ($item) {
                    return $item->getFieldPath();
                }, $changes[$id]->getArrayCopy());

                $this->assertEquals(['innerClass'], $changedPaths);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_whatChanged_WhenOuterPropertyChanged_FieldPathShouldBeEmpty(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {

                // arrange
                $doc = new OuterClass();
                $doc->setA("outerValue");
                $innerClass = new InnerClass();
                $innerClass->setA("innerClass");
                $doc->setInnerClass($innerClass);

                $id = "docs/1";
                $session->store($doc, $id);
                $session->saveChanges();

                $doc->setA("newOuterValue");

                // action
                $changes = $session->advanced()->whatChanged();

                // assert
                $changedPaths = array_map(function ($item) {
                    return $item->getFieldPath();
                }, $changes[$id]->getArrayCopy());

                $this->assertEquals([''], $changedPaths);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_whatChanged_WhenInnerPropertyInArrayChanged_ShouldReturnWithRelevantPath(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                // arrange
                $doc = new OuterClass();
                $doc->setA("outerValue");
                $innerClass = new InnerClass();
                $innerClass->setA("innerValue");

                $innerClasses = new InnerClassArray();
                $innerClasses->append($innerClass);
                $doc->setInnerClasses($innerClasses);

                $id = "docs/1";
                $session->store($doc, $id);
                $session->saveChanges();

                $doc->getInnerClasses()[0]->setA("newInnerValue");

                // action
                $changes = $session->advanced()->whatChanged();

                // assert
                $changedPaths = array_map(function ($item) {
                    return $item->getFieldPath();
                }, $changes[$id]->getArrayCopy());

                $this->assertEquals(['innerClasses[0]'], $changedPaths);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_whatChanged_WhenArrayPropertyInArrayChangedFromNull_ShouldReturnWithRelevantPath(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                // arrange
                $doc = new OuterClass();

                $innerClassMatrix = new InnerClassMatrix();
                $innerClassMatrix->append(new InnerClassArray());
                $doc->setInnerClassMatrix($innerClassMatrix);
                $id = "docs/1";
                $session->store($doc, $id);
                $session->saveChanges();

                $innerClasses = new InnerClassArray();
                $innerClasses->append(new InnerClass());
                $doc->getInnerClassMatrix()[0] = $innerClasses;

                // action
                $changes = $session->advanced()->whatChanged();

                // assert
                $changedPaths = array_map(function ($item) {
                    return $item->getFieldPath();
                }, $changes[$id]->getArrayCopy());

                $this->assertEquals(['innerClassMatrix[0]'], $changedPaths);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_whatChanged_WhenInMatrixChanged_ShouldReturnWithRelevantPath(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                // arrange
                $doc = new OuterClass();

                $innerClass = new InnerClass();
                $innerClass->setA("oldValue");

                $innerClasses = new InnerClassArray();
                $innerClasses->append($innerClass);

                $innerClassMatrix = new InnerClassMatrix();
                $innerClassMatrix->append($innerClasses);

                $doc->setInnerClassMatrix($innerClassMatrix);
                $id = "docs/1";
                $session->store($doc, $id);
                $session->saveChanges();

                $doc->getInnerClassMatrix()[0][0]->setA("newValue");

                // action
                $changes = $session->advanced()->whatChanged();

                // assert
                $changedPaths = array_map(function ($item) {
                    return $item->getFieldPath();
                }, $changes[$id]->getArrayCopy());

                $this->assertEquals(['innerClassMatrix[0][0]'], $changedPaths);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_whatChanged_WhenAllNamedAPropertiesChanged_ShouldReturnDifferentPaths(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                // arrange
                $doc = new OuterClass();
                $doc->setA("outerValue");

                $innerClass = new InnerClass();
                $innerClass->setA("innerValue");
                $doc->setInnerClass($innerClass);

                $doc->setMiddleClass(new MiddleClass());

                $innerClass2 = new InnerClass();
                $innerClass2->setA("oldValue");

                $innerClasses2 = new InnerClassArray();
                $innerClasses2->append($innerClass2);
                $doc->setInnerClasses($innerClasses2);

                $innerClass3 = new InnerClass();
                $innerClass3->setA("oldValue");

                $innerClasses3 = new InnerClassArray();
                $innerClasses3->append($innerClass3);
                $innerClassMatrix3 = new InnerClassMatrix();
                $innerClassMatrix3->append($innerClasses3);
                $doc->setInnerClassMatrix($innerClassMatrix3);

                $id = "docs/1";
                $session->store($doc, $id);
                $session->saveChanges();

                $doc->setA("newOuterValue");
                $doc->getInnerClass()->setA("newInnerValue");
                $doc->getMiddleClass()->setA(new InnerClass());
                $doc->getInnerClasses()[0]->setA("newValue");
                $doc->getInnerClassMatrix()[0][0]->setA("newValue");

                // action
                $changes = $session->advanced()->whatChanged();

                // assert
                $changedPaths = array_map(function ($item) {
                    return $item->getFieldPath();
                }, $changes[$id]->getArrayCopy());

                $this->assertCount(5, $changedPaths);
                $this->assertContains("", $changedPaths);
                $this->assertContains("innerClass", $changedPaths);
                $this->assertContains("middleClass", $changedPaths);
                $this->assertContains("innerClasses[0]", $changedPaths);
                $this->assertContains("innerClassMatrix[0][0]", $changedPaths);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
