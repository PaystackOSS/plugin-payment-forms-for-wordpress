<?php
namespace paystack\payment_forms;

class Payments_List_Table extends \WP_List_Table
{

    public function prepare_items() {
        $post_id  = $_GET['form'];
        $currency = get_post_meta( $post_id, '_currency', true );

        $data      = array();
        $alldbdata = pff_paystack()->helpers->get_payments_by_id( $post_id );

        foreach ( $alldbdata as $key => $dbdata ) {
            $newkey = $key + 1;
            if ( $dbdata->txn_code_2 != "" ) {
                $txn_code = $dbdata->txn_code_2;
            } else {
                $txn_code = $dbdata->txn_code;
            }
            $data[] = array(
                'id'  => $newkey,
                'email' => '<a href="mailto:' . $dbdata->email . '">' . $dbdata->email . '</a>',
                'amount' => $currency . '<b>' . number_format($dbdata->amount) . '</b>',
                'txn_code' => $txn_code,
                'metadata' => format_data($dbdata->metadata),
                'date'  => $dbdata->created_at
            );
        }

        $columns  = $this->get_columns();
        $hidden   = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        usort($data, array(&$this, 'sort_data'));

        $perPage     = 20;
        $currentPage = $this->get_pagenum();
        $totalItems  = count($data);

        $this->set_pagination_args(
            array(
                'total_items' => $totalItems,
                'per_page'    => $perPage
            )
        );
        $data                  = array_slice( $data, ( ( $currentPage - 1 ) * $perPage ), $perPage );
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->items           = $data;

        $rows = count( $alldbdata );
        return $rows;
    }

	/**
	 * Returns the headers and keys for our column headers
	 *
	 * @return array
	 */
    public function get_columns()
    {
        $columns = array(
            'id'  => '#',
            'email' => __( 'Email', 'paystack_forms' ),
            'amount' => __( 'Amount', 'paystack_forms' ),
            'txn_code' => __( 'Txn Code', 'paystack_forms' ),
            'metadata' => __( 'Data', 'paystack_forms' ),
            'date'  => __( 'Date', 'paystack_forms' ),
        );
        return $columns;
    }
    /**
     * Returns an array of the hidden columns
     *
     * @return array
     */
    public function get_hidden_columns()
    {
        return array();
    }
    public function get_sortable_columns()
    {
        return array(
			'email' => array(
				'email', false
			),
			'date' => array(
				'date', false
			),
			'amount' => array(
				'amount', false
			)
		);
    }
    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data($data)
    {
        return $data;
    }
    /**
     * Define what data to show on each column of the table
     *
     * @param Array  $item        Data
     * @param String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
        case 'id':
        case 'email':
        case 'amount':
        case 'txn_code':
        case 'metadata':
        case 'date':
            return $item[$column_name];
        default:
            return print_r($item, true);
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data($a, $b)
    {
        $orderby = 'date';
        $order = 'desc';
        if (!empty($_GET['orderby'])) {
            $orderby = $_GET['orderby'];
        }
        if (!empty($_GET['order'])) {
            $order = $_GET['order'];
        }
        $result = strcmp($a[$orderby], $b[$orderby]);
        if ($order === 'asc') {
            return $result;
        }
        return -$result;
    }
}