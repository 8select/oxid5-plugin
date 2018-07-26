<?php

/**
 * 8select export
 *
 */
class eightselect_export_static extends eightselect_export_abstract
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'eightselect_export_static';

    /**
     * Set static fields (not configurable ones)
     */
    public function run()
    {
        /* ToDo: 'model' is the virtual parent article */
        !isset($this->_aCsvAttributes['model']) ? null : $this->_aCsvAttributes['model'] = '';

        !isset($this->_aCsvAttributes['sku']) ? null : $this->_aCsvAttributes['sku'] = $this->_oArticle->oxarticles__oxartnum->value;
        !isset($this->_aCsvAttributes['status']) ? null : $this->_aCsvAttributes['status'] = (int)$this->_oArticle->isBuyable();
        !isset($this->_aCsvAttributes['name1']) ? null : $this->_aCsvAttributes['name1'] = $this->_oArticle->oxarticles__oxtitle->value ? $this->_oArticle->oxarticles__oxtitle->value : $this->_oParent->oxarticles__oxtitle->value;
        !isset($this->_aCsvAttributes['produkt_url']) ? null : $this->_aCsvAttributes['produkt_url'] = $this->_oArticle->getLink();
        !isset($this->_aCsvAttributes['bilder']) ? null : $this->_aCsvAttributes['bilder'] = $this->_getPictures();
        !isset($this->_aCsvAttributes['beschreibung']) ? null : $this->_aCsvAttributes['beschreibung'] = $this->_oArticle->getLongDescription();
        !isset($this->_aCsvAttributes['beschreibung1']) ? null : $this->_aCsvAttributes['beschreibung1'] = $this->_oArticle->oxarticles__oxshortdesc->value;

        /** @var oxManufacturer $oManufacturer */
        $oManufacturer = $this->_oArticle->getManufacturer();
        if ($oManufacturer) {
            !isset($this->_aCsvAttributes['marke']) ? null : $this->_aCsvAttributes['marke'] = $oManufacturer->oxmanufacturers__oxtitle->value;
        }

        if (isset($this->_aCsvAttributes['angebots_preis'])) {
            /** @var oxPrice $oPrice */
            $oPrice = $this->_oArticle->getPrice();
            if ($oPrice) {
                $this->_aCsvAttributes['angebots_preis'] = $oPrice->getPrice();
            }
        }

        if (isset($this->_aCsvAttributes['streich_preis'])) {
            $this->_aCsvAttributes['streich_preis'] = $this->_oArticle->getBasePrice();
        }
    }

    private function _getPictures()
    {
        $aPictureUrls = [];
        $iPicCount = $this->getConfig()->getConfigParam('iPicCount');
        for ($i = 1; $i <= $iPicCount; $i++) {
            $sPicUrl = $this->_oArticle->getPictureUrl($i);
            if (strpos($sPicUrl, 'nopic.jpg') === false) {
                $aPictureUrls[] = $sPicUrl;
            }
        }

        return implode(eightselect_export::EIGHTSELECT_CSV_MULTI_DELIMITER, $aPictureUrls);
    }
}