<?php
// food_order_content.php
// Pure content partial for the food order tab
// Requires: $conn, $_SESSION, $active_booking, $menu_items already fetched
?>

<?php if (!$active_booking): ?>
    <div class="text-center py-5">
        <div class="fs-1 mb-3"></div>
        <h5 class="fw-bold">You're not checked in yet</h5>
        <p class="text-muted">In-room food orders are available once the receptionist checks you in.</p>
    </div>
<?php else: ?>

    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="fw-bold mb-0"> Order from our menu</h6>
            <span class="badge bg-primary rounded-pill" id="cartCount">0 items</span>
        </div>

        <form action="../service_submit.php" method="POST" id="foodForm">
            <input type="hidden" name="booking_id" value="<?php echo $active_booking['booking_id']; ?>">
            <input type="hidden" name="type" value="food">
            <input type="hidden" name="redirect" value="user/my_stay.php">
            <textarea name="description" class="d-none" id="foodDesc"></textarea>

            <?php
            $prev_cat = '';
            $menu_items->data_seek(0);
            while ($item = $menu_items->fetch_assoc()):
                if ($item['category'] !== $prev_cat):
                    $prev_cat = $item['category'];
            ?>
            <p class="text-muted small fw-semibold text-uppercase mt-3 mb-2">
                <?php echo htmlspecialchars($item['category']); ?>
            </p>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center border rounded-3 px-3 py-2 mb-2 food-row">
                <div class="d-flex align-items-center gap-3">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>"
                        class="rounded-2 img-cover" style="height:44px;width:44px;"
                        alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <div>
                        <div class="fw-semibold small"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="text-primary fw-bold small">৳<?php echo number_format($item['price']); ?></div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-0 qty-btn"
                        data-action="minus" data-id="<?php echo $item['menu_id']; ?>"
                        data-price="<?php echo $item['price']; ?>"
                        data-name="<?php echo htmlspecialchars($item['name']); ?>">−</button>
                    <span class="fw-bold small qty-display" id="qd_<?php echo $item['menu_id']; ?>">0</span>
                    <button type="button" class="btn btn-outline-primary btn-sm px-2 py-0 qty-btn"
                        data-action="plus" data-id="<?php echo $item['menu_id']; ?>"
                        data-price="<?php echo $item['price']; ?>"
                        data-name="<?php echo htmlspecialchars($item['name']); ?>">+</button>
                    <input type="hidden" name="food[<?php echo $item['menu_id']; ?>]"
                        id="qi_<?php echo $item['menu_id']; ?>" value="0">
                </div>
            </div>
            <?php endwhile; ?>

            <!-- Order total -->
            <div class="bg-primary text-white rounded-3 p-3 mt-4 d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Order Total</span>
                <span class="fw-bold fs-5" id="orderTotal">৳0</span>
            </div>

            <div class="d-grid mt-3">
                <button type="submit" class="btn btn-success rounded-pill fw-semibold py-2"
                    id="placeOrderBtn" disabled>
                    Place Order 
                </button>
            </div>
        </form>
    </div>

    <script>
    // Food order cart logic
    (function() {
        let orderTotal = 0, itemCount = 0;
        const orderItems = {};

        document.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const action = this.dataset.action;
                const price = parseFloat(this.dataset.price);
                const name = this.dataset.name;
                const input = document.getElementById('qi_' + id);
                const disp  = document.getElementById('qd_' + id);

                let qty = parseInt(input.value) || 0;
                if (action === 'plus')  qty = Math.min(qty + 1, 10);
                if (action === 'minus') qty = Math.max(qty - 1, 0);

                input.value = qty;
                disp.textContent = qty;
                disp.style.color = qty > 0 ? 'var(--bs-primary)' : '';

                orderItems[id] = { name, qty, price };
                orderTotal = Object.values(orderItems).reduce((s, i) => s + i.price * i.qty, 0);
                itemCount  = Object.values(orderItems).reduce((s, i) => s + i.qty, 0);

                document.getElementById('orderTotal').textContent = '৳' + orderTotal.toLocaleString('en-BD');
                document.getElementById('cartCount').textContent  = itemCount + (itemCount === 1 ? ' item' : ' items');

                const ob = document.getElementById('placeOrderBtn');
                ob.disabled = itemCount === 0;
                ob.textContent = itemCount > 0
                    ? `Place Order (${itemCount} item${itemCount > 1 ? 's' : ''}) `
                    : 'Place Order ';
            });
        });

        document.getElementById('foodForm')?.addEventListener('submit', function(e) {
            const lines = Object.values(orderItems)
                .filter(i => i.qty > 0)
                .map(i => `${i.name} x${i.qty}`);
            if (lines.length === 0) {
                e.preventDefault();
                alert('Please select at least one item.');
                return;
            }
            document.getElementById('foodDesc').value = lines.join(', ');
        });
    })();
    </script>

<?php endif; ?>
