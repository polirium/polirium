<div style="font-family: 'Arial', sans-serif; font-size: 12px; line-height: 1.4; color: #000;">
    <div style="text-align: center; margin-bottom: 10px;">
        <div style="font-weight: bold; font-size: 16px;">@{{ store_name }}</div>
        <div>@{{ store_address }}</div>
        <div>Hotline: @{{ store_phone }}</div>
    </div>

    <div style="text-align: center; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 10px;">
        <div style="font-weight: bold; font-size: 14px; text-transform: uppercase;">HÓA ĐƠN BÁN HÀNG</div>
        <div>Số: @{{ code }}</div>
        <div>@{{ day }}/@{{ month }}/@{{ year }}</div>
    </div>

    <div style="margin-bottom: 10px;">
        <div><strong>KH:</strong> @{{ customer_name }}</div>
        <div><strong>SĐT:</strong> @{{ customer_phone }}</div>
        <div><strong>ĐC:</strong> @{{ customer_address }}</div>
    </div>

    <div style="margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 10px;">
        @{{ items_table|raw }}
    </div>

    <div style="margin-bottom: 10px;">
        <div style="display: flex; justify-content: space-between;">
            <span>Tổng tiền:</span>
            <strong>@{{ total_amount }}</strong>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>Chiết khấu:</span>
            <span>@{{ discount_amount }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin-top: 5px;">
            <span>THANH TOÁN:</span>
            <span>@{{ grand_total }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 5px; font-style: italic;">
            <span>Khách trả:</span>
            <span>@{{ customer_paid }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-style: italic;">
            <span>Tiền thừa:</span>
            <span>@{{ change_amount }}</span>
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px; font-style: italic;">
        <div>@{{ note }}</div>
        <div style="margin-top: 10px;">Cảm ơn và hẹn gặp lại!</div>
        <div style="font-size: 10px; margin-top: 5px;">Powered by Polirium</div>
    </div>
</div>
