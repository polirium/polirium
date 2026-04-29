<?php

return [
    'form_setting' => 'Print Forms',
    'name' => 'Print Forms',
    'create' => 'Create New Print Form',
    'edit' => 'Edit Print Form',

    // Editor UI
    'invoice' => 'Invoice',
    'receipt' => 'Receipt',
    'payment' => 'Payment',
    'form_list' => 'Print Form List',
    'create_new_form' => 'Create New Form',
    'form_name' => 'Form Name',
    'status' => 'Status',
    'actions' => 'Actions',
    'active' => 'Active',
    'hidden' => 'Hidden',
    'set_as_main' => 'Set as Main',
    'delete_confirm' => 'Are you sure you want to delete this print form?',
    'no_forms_yet' => 'No print forms yet',
    'no_forms_subtitle' => 'Click "Create New Form" to start.',
    'preview' => 'Preview',
    'select_form_to_preview' => 'Select a form to preview',
    'print_preview' => 'Print Preview',
    'select_form_desc' => 'Select a form from the left list to preview, edit or print.',
    'paper_size' => 'Paper Size',
    'size_a4' => 'A4 (210mm)',
    'size_a5' => 'A5 (148mm)',
    'size_k80' => 'K80 (80mm)',
    'size_k57' => 'K57 (57mm)',
    'formatting_guide' => 'Formatting Guide',
    'formatting_guide_content' => '
        <ul>
            <li>Use <strong>table</strong> with width="100%" for layout.</li>
            <li>Avoid fixed width (px), use percentage (%).</li>
            <li>For K80/K57, keep content simple with few columns.</li>
            <li>Use classes <strong>.text-center</strong>, <strong>.text-right</strong> for alignment.</li>
            <li>Use <strong>&lt;br&gt;</strong> for line breaks instead of &lt;p&gt; to save paper.</li>
        </ul>
    ',
    'help' => 'Help',

    // Variables
    'variable_list' => 'Variables List',
    'click_to_copy' => 'Click to copy or drag & drop into editor.',
    'click_to_copy_tooltip' => 'Click to copy',
    'copied' => 'Copied',
    'content' => 'Content',
    'edit_content' => 'Edit Content',
    'enter_form_name' => 'Enter form name',
    'form_name_label' => 'Form Name',
    'changes_applied' => '* Changes will be applied immediately to this print form.',
    'select_paper_size' => 'Select paper size before editing for best formatting.',
    'help_content_guide' => 'Content Guide',

    // Variable Groups
    'store_info' => 'Store Info',
    'order_info' => 'Order Info',
    'customer_info' => 'Customer Info',
    'items_info' => 'Items List',
    'payment_info' => 'Payment Info',

    // Variable Descriptions
    'var_store_logo' => 'Store Logo',
    'var_store_name' => 'Store Name',
    'var_store_address' => 'Store Address',
    'var_store_phone' => 'Store Phone',
    'var_order_code' => 'Order Code',
    'var_created_at' => 'Created Day',
    'var_created_month' => 'Created Month',
    'var_created_year' => 'Created Year',
    'var_note' => 'Order Note',
    'var_customer_name' => 'Customer Name',
    'var_customer_phone' => 'Customer Phone',
    'var_customer_address' => 'Customer Address',
    'var_customer_district' => 'Customer District',
    'var_items_table' => 'Items Table',
    'var_total_amount' => 'Total Amount',
    'var_discount_amount' => 'Discount Amount',
    'var_discount_percent' => 'Discount Percent',
    'var_grand_total' => 'Grand Total',
    'var_customer_paid' => 'Customer Paid',
    'var_change_amount' => 'Change Amount',
];
