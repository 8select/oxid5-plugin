<?php

/**
 * 8select export class.
 */
class eightselect_admin_export_do extends DynExportBase
{
    /**
     * Export class name
     *
     * @var string
     */
    public $sClassDo = "eightselect_admin_export_do";

    /**
     * Export ui class name
     *
     * @var string
     */
    public $sClassMain = "eightselect_admin_export_main";

    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = "eightselect_admin_export_do.tpl";

    /** @var array */
    private $_aParent = [];

    /** @var array */
    private $_aVirtual = [];

    /**
     * Prepares export
     */
    public function start()
    {
        $this->_aViewData['refresh'] = 0;
        $this->_aViewData['iStart'] = 0;

        // prepare it
        $iEnd = $this->prepareExport();
        oxRegistry::getSession()->setVariable("iEnd", $iEnd);
        $this->_aViewData['iEnd'] = $iEnd;

        $sType = oxRegistry::getConfig()->getRequestParameter("do_full") ? 'do_full' : 'do_update';
        $this->_aViewData['sType'] = $sType;
    }

    /**
     * Does export
     */
    public function run()
    {
        $blFull = (bool)oxRegistry::getConfig()->getRequestParameter('do_full');

        /** @var eightselect_export $oEightSelectExport */
        $oEightSelectExport = oxNew('eightselect_export');

        /** @var eightselect_log $oEightSelectLog */
        $oEightSelectLog = oxNew('eightselect_log');
        $oEightSelectLog->startExport($blFull);

        $mDateTime = $oEightSelectLog->getLastSuccessExportDate($blFull);
        $oEightSelectLog->setLastSuccessExportDate($blFull);

        try {
            $this->sExportFileName = $oEightSelectExport->getExportFileName($blFull);
            $this->_sFilePath = $this->getConfig()->getConfigParam('sShopDir') . "/" . $this->sExportPath . $this->sExportFileName;
            parent::run();
            $oEightSelectLog->successExport();
        } catch (UnexpectedValueException $oEx) {
            $this->stop(eightselect_export::$err_nofeedid);
            $oEightSelectLog->errorExport($oEx->getMessage());
            $oEightSelectLog->setLastSuccessExportDate($blFull, $mDateTime);
        }
    }

    /**
     * Does export line by line on position iCnt
     *
     * @param integer $iCnt export position
     *
     * @return bool
     */
    public function nextTick($iCnt)
    {
        $iExportedItems = $iCnt;
        $blContinue = false;

        static $oEightSelectTmpExport = null;
        if ($oEightSelectTmpExport === null) {
            $oEightSelectTmpExport = oxNew('eightselect_export');
        }

        /** @var oxArticle $oArticle */
        if ($oArticle = $this->getOneArticle($iCnt, $blContinue)) {

            // set parent article (performance loading)
            if (!isset($this->_aParent[$oParent->oxarticles__oxparentid->value])) {
                // clear parent from other variant
                $this->_aParent = [];
                $oParent = $oArticle->getParentArticle();
                $this->_aParent[$oArticle->oxarticles__oxparentid->value]['article_object'] = $oParent;
                $this->_aParent[$oArticle->oxarticles__oxparentid->value]['category_string'] = $this->getCategoryString($oParent, ' / ');
            }

            /** @var eightselect_export $oEightSelectExport */
            $oEightSelectExport = clone $oEightSelectTmpExport;
            $oEightSelectExport->setArticle($oArticle);
            $oEightSelectExport->setParent($this->_aParent[$oArticle->oxarticles__oxparentid->value]['article_object']);
            $oEightSelectExport->setCategory($this->_aParent[$oArticle->oxarticles__oxparentid->value]['category_string']);

            // set header if it's the first article
            if ((int)$iCnt === 0) {
                fwrite($this->fpFile, $oEightSelectExport->getCsvHeader());
            }

            // set virtual article for each color
            $sVirtualMasterSku = $oEightSelectExport->getVirtualMasterSku();
            if ($sVirtualMasterSku && !isset($this->_aVirtual[$oArticle->oxarticles__oxparentid->value][$sVirtualMasterSku])) {
                /** @var eightselect_export $oEightSelectExport */
                $oEightSelectExportVirtual = clone $oEightSelectExport;
                $oEightSelectExportVirtual->setArticle($this->_aParent[$oArticle->oxarticles__oxparentid->value]['article_object']);

                // clear parent from other variant
                if (!isset($this->_aVirtual[$oArticle->oxarticles__oxparentid->value])) {
                    $this->_aVirtual = [];
                }

                $this->_aVirtual[$oArticle->oxarticles__oxparentid->value][$sVirtualMasterSku] = $oEightSelectExportVirtual;

                // write virtual parent at first time in CSV
                fwrite($this->fpFile, $oEightSelectExportVirtual->getCsvLineVirtual($sVirtualMasterSku));
            }

            $oEightSelectExport->setVirtual($this->_aVirtual[$oArticle->oxarticles__oxparentid->value][$sVirtualMasterSku]);

            // write variant to CSV
            fwrite($this->fpFile, $oEightSelectExport->getCsvLine());

            return ++$iExportedItems;
        }

        return $blContinue;
    }

    /**
     * inserts articles into heaptable
     *
     * @param string $sHeapTable heap table name
     * @param string $sCatAdd category id filter (part of sql)
     *
     * @return bool
     */
    protected function _insertArticles($sHeapTable, $sCatAdd)
    {
        $oDB = oxDb::getDb();

        $iExpLang = oxRegistry::getConfig()->getRequestParameter("iExportLanguage");
        if (!isset($iExpLang)) {
            $iExpLang = oxRegistry::getSession()->getVariable("iExportLanguage");
        }

        $oArticle = oxNew('oxarticle');
        $oArticle->setLanguage($iExpLang);

        $sArticleTable = getViewName("oxarticles", $iExpLang);
        $sO2CView = getViewName('oxobject2category', $iExpLang);

        $sSelect = "INSERT INTO {$sHeapTable} ";
        $sSelect .= "SELECT oxarticles.OXID FROM {$sArticleTable} as oxarticles, {$sO2CView} AS oxobject2category ";
        $sSelect .= "WHERE OXPARENTID != '' AND oxarticles.OXPARENTID = oxobject2category.OXOBJECTID ";

        if ($sCatAdd) {
            $sSelect .= $sCatAdd;
        }

        // get only last changed articles
        $blFull = (bool)oxRegistry::getConfig()->getRequestParameter('do_full');
        if (!$blFull) {
            /** @var eightselect_log $oEightSelectLog */
            $oEightSelectLog = oxNew('eightselect_log');
            $mDateTime = $oEightSelectLog->getLastSuccessExportDate($blFull);
            if ($mDateTime) {
                $sSelect .= " AND oxarticles.OXTIMESTAMP >= " . $oDB->quote($mDateTime);
            }
        }

        $sSelect .= " GROUP BY oxarticles.OXID ORDER BY OXPARENTID, OXARTNUM ASC";

        return $oDB->execute($sSelect) ? true : false;
    }

    /**
     * removes parent articles so that we only have variants itself
     *
     * @param string $sHeapTable table name
     */
    protected function _removeParentArticles($sHeapTable)
    {
        /* we don't have parent articles in heap-table, so we can skip that */
    }
}
