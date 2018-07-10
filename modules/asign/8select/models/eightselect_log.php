<?php

/**
 * Logging manager
 *
 */
class eightselect_log extends oxBase
{
    /** @var string */
    public static $ACTION_EXPORT_FULL = 'Export Full';

    /** @var string */
    public static $ACTION_EXPORT_UPD = 'Export Update';

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'eightselect_log';

    /**
     * Core database table name. $sCoreTable could be only original data table name and not view name.
     *
     * @var string
     */
    protected $_sCoreTable = 'eightselect_log';

    /**
     * @var bool
     */
    protected $_blFullExport = null;

    /**
     * Add a self-defined message to log
     *
     * @param string $sAction
     * @param string $sMessage
     */
    public function addLog($sAction, $sMessage)
    {
        $this->init();
        $this->_setAction($sAction);
        $this->_setMessage($sMessage);
        $this->_setDate();
        $this->save();
    }

    /**
     * Add entry in logging table
     *
     * @param bool $blFull
     */
    public function startExport($blFull)
    {
        $this->init();
        $this->_blFullExport = (bool)$blFull;
        $this->_setAction($blFull ? self::$ACTION_EXPORT_FULL : self::$ACTION_EXPORT_UPD);
        $this->_setMessage('Start export');
        $this->save();
    }

    /**
     * Write success message in entry
     */
    public function successExport()
    {
        $this->_setMessage('Export successfully');
        $this->_setDate();
        $this->save();
    }

    public function errorExport($sMessage)
    {
        $this->_setMessage("Export error\n{$sMessage}");
        $this->save();
    }

    /**
     * @param bool $blFull
     * @return bool
     */
    public function loadLastSuccessExport($blFull)
    {
        $blCoreTableUsage = $this->getForceCoreTableUsage();

        $this->_addField('oxid', 0);
        $this->_addField('eightselect_date', 0);
        $sSelect = $this->buildSelectString(array($this->getViewName() . '.EIGHTSELECT_ACTION' => $blFull ? self::$ACTION_EXPORT_FULL : self::$ACTION_EXPORT_UPD));
        $sSelect .= ' AND '.$this->getViewName() . '.EIGHTSELECT_DATE != "0000-00-00 00:00:00"';
        $sSelect .= ' ORDER BY EIGHTSELECT_DATE DESC';
        $this->_isLoaded = $this->assignRecord($sSelect);

        $this->setForceCoreTableUsage($blCoreTableUsage);

        return $this->_isLoaded;
    }

    /**
     * Set an action name
     *
     * @param string $sActionName
     */
    private function _setAction($sActionName)
    {
        $this->eightselect_log__eightselect_action = new oxField($sActionName);
    }

    /**
     * Set a message
     *
     * @param string $sMessage
     */
    private function _setMessage($sMessage)
    {
        $this->eightselect_log__eightselect_message = new oxField($sMessage);
    }

    /**
     * Set to current datetime
     */
    private function _setDate()
    {
        $this->eightselect_log__eightselect_date = new oxField(date('Y-m-d H:i:s'));
    }
}