<?php


namespace PandoraFMS\Dashboard;

/**
 * Dashboard manager.
 */
class Cell
{

    /**
     * Dasboard ID.
     *
     * @var integer
     */
    private $dashboardId;

    /**
     * Cell ID.
     *
     * @var integer
     */
    private $cellId;

    /**
     * Data cell.
     *
     * @var array
     */
    private $fieldsCell;


    /**
     * Undocumented function
     *
     * @param array|integer $data        Id Cell or data forn new(insert) Cell.
     * @param integer       $dashboardId Id Dashboard.
     */
    public function __construct($data, int $dashboardId)
    {
        $this->dashboardId = $dashboardId;

        // Check exists Cell id.
        if (is_array($data) === false && empty($data) === false) {
            $this->cellId = $data;
        } else {
            $this->cellId = $this->set($data);
        }

        $this->fieldsCell = $this->get();
        return $this;
    }


    /**
     * Retrieve a cell definition.
     *
     * @return array cell data.
     */
    public function get()
    {
        global $config;

        $sql = sprintf(
            'SELECT *
            FROM twidget_dashboard
            WHERE id = %d',
            $this->cellId
        );

        $data = \db_get_row_sql($sql);

        if ($data === false) {
            return [];
        }

        return $data;
    }


    /**
     * Create Cell widget layout.
     *
     * @param array $position Array position widgets.
     *
     * @return integer
     */
    public function set(array $position):int
    {
        global $config;

        if (isset($position['order']) === true) {
            $order = $position['order'];
            unset($position['order']);
        } else {
            $order = count(self::getCells($this->dashboardId));
        }

        $position = json_encode($position);

        $values = [
            'id_dashboard' => $this->dashboardId,
            'position'     => $position,
            'order'        => $order,
        ];

        // Insert.
        $res = \db_process_sql_insert(
            'twidget_dashboard',
            $values
        );

        if ($res === false) {
            $res = 0;
        }

        return $res;
    }


    /**
     * Save Cell widget layout.
     *
     * @param array        $position Array position widgets.
     * @param integer|null $idWidget Id widget insert to cell.
     * @param array        $options  Options for widget.
     *
     * @return integer
     */
    public function put(
        array $position=[],
        ?int $idWidget=null,
        array $options=[]
    ):int {
        global $config;

        // Position.
        if (empty($position) !== true) {
            $order = 0;
            if (isset($position['order']) === true) {
                $order = $position['order'];
                unset($position['order']);
            }

            $position = json_encode($position);
        } else {
            $order = $this->fieldsCell['order'];
            $position = $this->fieldsCell['position'];
        }

        // Id widget.
        if (isset($idWidget) === false) {
            $idWidget = $this->fieldsCell['id_widget'];
        }

        // Options for widget.
        if (empty($options) !== true) {
            $options = json_encode($options, JSON_UNESCAPED_UNICODE);
        } else {
            $options = $this->fieldsCell['options'];
        }

        // Values.
        $values = [
            'id_dashboard' => $this->dashboardId,
            'position'     => $position,
            'options'      => $options,
            'order'        => $order,
            'id_widget'    => $idWidget,
        ];

        // Update.
        $res = \db_process_sql_update(
            'twidget_dashboard',
            $values,
            ['id' => $this->cellId]
        );

        if ($res === false) {
            $res = 0;
        }

        return $res;
    }


    /**
     * Remove Cell layout.
     *
     * @return integer
     */
    public function delete():int
    {
        global $config;

        // Delete.
        $res = db_process_sql_delete(
            'twidget_dashboard',
            ['id' => $this->cellId]
        );
        if ($res === false) {
            $res = 0;
        }

        return $res;
    }


    /**
     * Get Cells widget.
     *
     * @param integer $dashboardId Id dashboard.
     *
     * @return array
     */
    public static function getCells(int $dashboardId):array
    {
        $cells = db_get_all_rows_filter(
            'twidget_dashboard',
            [
                'id_dashboard' => $dashboardId,
                'order'        => [
                    'order' => 'ASC',
                    'field' => \db_encapsule_fields_with_same_name_to_instructions('order'),
                ],
            ]
        );

        if (empty($cells) === true) {
            return [];
        }

        return $cells;
    }


}
