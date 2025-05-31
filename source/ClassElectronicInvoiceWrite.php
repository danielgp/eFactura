<?php

/*
 * Copyright (c) 2024 - 2025 Daniel Popiniuc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *    Daniel Popiniuc
 */

namespace danielgp\efactura;

class ClassElectronicInvoiceWrite
{
    use TraitVersions;

    protected \XMLWriter $objXmlWriter;

    private function setDecisionElements(array $arrayInput, string $strKey, string $strTag, string $strCategory): void
    {
        switch($strCategory) {
            case 'ElementsOrdered':
                $this->setElementsOrdered([
                    'commentParentKey' => $strKey,
                    'data'             => $arrayInput['data'][$strTag],
                    'tag'              => $strTag,
                ]);
                break;
            case 'MultipleElementsOrdered':
                $this->setMultipleElementsOrdered([
                    'commentParentKey' => $strKey,
                    'data'             => $arrayInput['data'][$strTag],
                    'tag'              => $strTag,
                ]);
                break;
            case 'SingleElementWithAttribute':
                $this->setSingleElementWithAttribute([
                    'commentParentKey' => $arrayInput['commentParentKey'],
                    'data'             => $arrayInput['data'][$strTag],
                    'tag'              => $strTag,
                ]);
                break;
        }
    }

    private function setElementComment(string $strKey): void
    {
        if (array_key_exists($strKey, $this->arraySettings['Comments'])) {
            $elementComment = $this->arraySettings['Comments'][$strKey];
            if (is_array($elementComment)) {
                foreach ($elementComment as $value) {
                    $this->objXmlWriter->writeComment($value);
                }
            } else {
                $this->objXmlWriter->writeComment($elementComment);
            }
        }
    }

    private function setElementsOrdered(array $arrayInput): void
    {
        $this->setElementComment($arrayInput['commentParentKey']);
        $this->objXmlWriter->startElement('cac:' . $arrayInput['tag']);
        $this->setExtraElement($arrayInput, 'Start');
        foreach ($this->arraySettings['CustomOrder'][$arrayInput['commentParentKey']] as $value) {
            if (array_key_exists($value, $arrayInput['data'])) { // because certain value are optional
                $key         = implode('_', [$arrayInput['commentParentKey'], $value]);
                $matches     = [];
                preg_match('/^.*(Amount|Quantity)$/', $value, $matches, PREG_OFFSET_CAPTURE);
                $strCategory = $this->setCategorizedVerifications([
                    'commentParentKey' => $arrayInput['commentParentKey'],
                    'data'             => $arrayInput['data'][$value],
                    'matches'          => $matches,
                    'tag'              => $value,
                ]);
                $this->setDecisionElements($arrayInput, $key, $value, $strCategory);
            }
        }
        $this->setExtraElement($arrayInput, 'End');
        $this->objXmlWriter->endElement(); // $arrayInput['tag']
    }

    private function setExtraElement(array $arrayInput, string $strType): void
    {
        if (in_array($arrayInput['tag'], ['AccountingCustomerParty', 'AccountingSupplierParty'])) {
            switch($strType) {
                case 'End':
                    $this->objXmlWriter->endElement();
                    break;
                case 'Start':
                    $this->objXmlWriter->startElement('cac:Party');
                    break;
            }
        }
    }

    private function setHeaderCommonBasicComponents(array $arrayElementWithData): void
    {
        $arrayCustomOrdered = $this->arraySettings['CustomOrder']['Header_CBC'];
        foreach ($arrayCustomOrdered as $value) {
            if (array_key_exists($value, $arrayElementWithData)) {
                $this->setElementComment($value);
                $this->objXmlWriter->writeElement('cbc:' . $value, $arrayElementWithData[$value]);
            }
        }
    }

    private function setMultipleElementsOrdered(array $arrayData): void
    {
        foreach ($arrayData['data'] as $value) {
            $strCommentParentKey = $this->setManageComment($arrayData['commentParentKey'], $value);
            $this->setElementsOrdered([
                'commentParentKey' => $strCommentParentKey,
                'data'             => $value,
                'tag'              => $arrayData['tag'],
            ]);
        }
    }

    private function setPrepareXml(string $strFile, array $arrayDocumentData, int $intIdent = 4): void
    {
        $this->objXmlWriter = new \XMLWriter();
        $this->objXmlWriter->openURI($strFile);
        $this->objXmlWriter->setIndent(true);
        $this->objXmlWriter->setIndentString(str_repeat(' ', $intIdent));
        $this->objXmlWriter->startDocument('1.0', 'UTF-8');
        $this->objXmlWriter->startElement($arrayDocumentData['DocumentTagName']);
        foreach ($arrayDocumentData['DocumentNameSpaces'] as $key => $value) {
            if ($key === '') {
                $strValue = sprintf($value, $arrayDocumentData['DocumentTagName']);
                $this->objXmlWriter->writeAttributeNS(null, 'xmlns', null, $strValue);
            } else {
                $this->objXmlWriter->writeAttributeNS('xmlns', $key, null, $value);
            }
        }
        if (array_key_exists('SchemaLocation', $arrayDocumentData)) {
            $this->objXmlWriter->writeAttribute('xsi:schemaLocation', $arrayDocumentData['SchemaLocation']);
        }
    }

    private function setProduceMiddleXml(array $arrayData): void
    {
        foreach ($this->arrayProcessing['OptionalElementsHeader'] as $key => $strLogicType) {
            if (array_key_exists($key, $arrayData)) {
                switch($strLogicType) {
                    case 'SingleCompany':
                        $this->setElementsOrdered([
                            'commentParentKey' => $key,
                            'data'             => $arrayData[$key]['Party'],
                            'tag'              => $key,
                        ]);
                        break;
                    default:
                        $arrayInput = [
                            'commentParentKey' => $key,
                            'data'             => $arrayData,
                            'tag'              => $key
                        ];
                        $this->setDecisionElements($arrayInput, $key, $key, $strLogicType);
                        break;
                }
            }
        }
    }

    private function setSingleComment(array $arrayInput): void
    {
        if (array_key_exists('commentParentKey', $arrayInput)) {
            $this->setElementComment(implode('_', [$arrayInput['commentParentKey'], $arrayInput['tag']]));
            if (str_ends_with($arrayInput['tag'], 'Quantity')) {
                $this->setElementComment(implode('_', [$arrayInput['commentParentKey'], $arrayInput['tag']
                    . 'UnitOfMeasure']));
            }
        }
    }

    private function setSingleElementWithAttribute(array $arrayInput): void
    {
        $this->setSingleComment($arrayInput);
        if (is_array($arrayInput['data']) && array_key_exists('value', $arrayInput['data'])) {
            $this->objXmlWriter->startElement('cbc:' . $arrayInput['tag']);
            foreach ($arrayInput['data'] as $key => $value) {
                if ($key !== 'value') { // if is not value, must be an attribute
                    $this->objXmlWriter->writeAttribute($key, $value);
                }
            }
            $this->objXmlWriter->writeRaw($this->setNumericValue($arrayInput['tag'], $arrayInput['data']));
            $this->objXmlWriter->endElement();
        } else {
            $this->objXmlWriter->writeElement('cbc:' . $arrayInput['tag'], $arrayInput['data']);
        }
    }

    public function writeElectronicInvoice(string $strFile, array $inData, array $arrayFeatures): void
    {
        $this->getProcessingDetails();
        $arrayData = $this->loadSettingsAndManageDefaults($inData, $arrayFeatures);
        if (!array_key_exists('Ident', $arrayFeatures)) {
            $arrayFeatures['Ident'] = 4;
        }
        $this->setPrepareXml($strFile, $arrayData, $arrayFeatures['Ident']);
        $this->setHeaderCommonBasicComponents($arrayData['Header']['CommonBasicComponents-2']);
        $this->setProduceMiddleXml($arrayData['Header']['CommonAggregateComponents-2']);
        // multiple Lines
        $this->setMultipleElementsOrdered([
            'commentParentKey' => 'Lines',
            'data'             => $arrayData['Lines'],
            'tag'              => $arrayData['DocumentTagName'] . 'Line',
        ]);
        $this->objXmlWriter->endElement(); // Invoice or CreditNote
        $this->objXmlWriter->flush();
    }
}
