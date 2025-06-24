<?php
// Start the session.
session_start();
if (!isset($_SESSION['user'])) header('location: login.php');

// Get all products.
$show_table = 'products';
$products = include('database/show.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Products - Inventory Management System</title>
    <?php include('partials/app-header-scripts.php'); ?>
</head>
<body>
    <div id="dashboardMainContainer">
        <?php include('partials/app-sidebar.php') ?>
        <div class="dasboard_content_container" id="dasboard_content_container">
            <?php include('partials/app-topnav.php') ?>
            <div class="dashboard_content">
                <?php if (in_array('product_view', $user['permissions'])) { ?>
                <div class="dashboard_content_main">		
                    <div class="row">
                        <div class="column column-12">
                            <h1 class="section_header"><i class="fa fa-list"></i> List of Products</h1>
                            <div class="section_content">
                                <div class="users">
                                    <table>
                                        <thead>
                                            <tr>												
                                                <th>#</th>					
                                                <th>Image</th>
                                                <th>Product Name</th>
                                                <th>Stock</th>
                                                <th width="20%">Description</th>
                                                <th width="15%">Suppliers</th>
                                                <th>Created By</th>
                                                <th>Created At</th>
                                                <th>Updated At</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($products as $index => $product) { 
                                                $qty_class = 'bgGreen';
                                                $qty_int = (int) $product['stock'];
                                                if ($qty_int <= 10) $qty_class = 'bgRed';
                                                if ($qty_int >= 11 && $qty_int <= 30) $qty_class = 'bgYellow';
                                            ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td class="firstName">
                                                        <img class="productImages" src="uploads/products/<?= $product['img'] ?>" alt="" />
                                                    </td>
                                                    <td class="lastName"><?= $product['product_name'] ?></td>
                                                    <td class="lastName <?= $qty_class ?>"><?= number_format($product['stock']) ?></td>
                                                    <td class="email"><?= $product['description'] ?></td>
                                                    <td class="email">
                                                        <?php
                                                            $supplier_list = '-';
                                                            $pid = $product['id'];
                                                            $stmt = $conn->prepare("
                                                                SELECT supplier_name 
                                                                FROM suppliers, productsuppliers 
                                                                WHERE 
                                                                    productsuppliers.product=$pid 
                                                                    AND 
                                                                    productsuppliers.supplier = suppliers.id
                                                            ");
                                                            $stmt->execute();
                                                            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                            if ($row) {																
                                                                $supplier_arr = array_column($row, 'supplier_name');
                                                                $supplier_list = '<li>' . implode("</li><li>", $supplier_arr);
                                                            }
                                                            echo $supplier_list;
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            $uid = $product['created_by'];
                                                            $stmt = $conn->prepare("SELECT * FROM users WHERE id=$uid");
                                                            $stmt->execute();
                                                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                                            $created_by_name = $row['first_name'] . ' ' . $row['last_name'];
                                                            echo $created_by_name;
                                                        ?>
                                                    </td>
                                                    <td><?= date('M d,Y @ h:i:s A', strtotime($product['created_at'])) ?></td>
                                                    <td><?= date('M d,Y @ h:i:s A', strtotime($product['updated_at'])) ?></td>
                                                    <td>
                                                        <a href="#" 
                                                           class="<?= in_array('product_edit', $user['permissions']) ? 'updateProduct' : 'accessDeniedErr' ?>" 
                                                           data-pid="<?= $product['id'] ?>"> 
                                                        <i class="fa fa-pencil"></i> Edit</a> | 
                                                        <a href="#" 
                                                           class="<?= in_array('product_delete', $user['permissions']) ? 'deleteProduct' : 'accessDeniedErr'?>" 
                                                           data-name="<?= $product['product_name'] ?>" 
                                                           data-pid="<?= $product['id'] ?>"> <i class="fa fa-trash"></i> Delete</a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                    <p class="userCount"><?= count($products) ?> products </p>
                                </div>
                            </div>
                        </div>
                    </div>					
                </div>
                <?php } else { ?>
                    <div id="errorMessage"> Access denied.</div>
                <?php } ?>
            </div>
        </div>
    </div>

<?php 
    include('partials/app-scripts.php'); 

    $show_table = 'suppliers';
    $suppliers = include('database/show.php');

    $suppliers_arr = [];
    foreach ($suppliers as $supplier) {
        $suppliers_arr[$supplier['id']] = $supplier['supplier_name'];
    }
    $suppliers_arr = json_encode($suppliers_arr);
?>
<script>
    var suppliersList = <?= $suppliers_arr ?>;

    function script() {
        var vm = this;

        this.registerEvents = function() {
            document.addEventListener('click', function(e) {
                targetElement = e.target; // Target element
                classList = targetElement.classList;

                // Delete Product
                if (classList.contains('deleteProduct')) {
                    e.preventDefault(); // Prevent default action.

                    pId = targetElement.dataset.pid;
                    pName = targetElement.dataset.name;

                    BootstrapDialog.confirm({
                        type: BootstrapDialog.TYPE_DANGER,
                        title: 'Delete Product',
                        message: 'Are you sure to delete <strong>' + pName + '</strong>?',
                        callback: function(isDelete) {
                            if (isDelete) {								
                                $.ajax({
                                    method: 'POST',
                                    data: {
                                        id: pId,
                                        table: 'products'
                                    },
                                    url: 'database/delete.php',
                                    dataType: 'json',
                                    success: function(data) {
                                        message = data.success ? 
                                            pName + ' successfully deleted!' : 'Error processing your request!';

                                        BootstrapDialog.alert({
                                            type: data.success ? BootstrapDialog.TYPE_SUCCESS : BootstrapDialog.TYPE_DANGER,
                                            message: message,
                                            callback: function() {
                                                if (data.success) location.reload();
                                            }
                                        });
                                    }
                                });
                            }
                        }
                    });
                }

                // Edit Product
                if (classList.contains('updateProduct')) {
                    e.preventDefault();

                    const productId = targetElement.dataset.pid;
                    const productRow = targetElement.closest('tr');
                    const productName = productRow.querySelector('.lastName').innerHTML;
                    const productStock = productRow.querySelector('.lastName.bgGreen, .lastName.bgYellow, .lastName.bgRed').innerHTML;
                    const productDescription = productRow.querySelector('.email').innerHTML;
                    const productImage = productRow.querySelector('.productImages').src;

                    // Get current suppliers for this product
                    const currentSuppliers = [];
                    const supplierLis = productRow.querySelectorAll('.email li');
                    supplierLis.forEach(li => {
                        const supplierName = li.textContent;
                        for (const [id, name] of Object.entries(suppliersList)) {
                            if (name === supplierName) {
                                currentSuppliers.push(id);
                                break;
                            }
                        }
                    });

                    // Create supplier checkboxes
                    let supplierOptions = '';
                    for (const [id, name] of Object.entries(suppliersList)) {
                        const checked = currentSuppliers.includes(id) ? 'checked' : '';
                        supplierOptions += `
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="suppliers[]" value="${id}" ${checked}>
                                    ${name}
                                </label>
                            </div>
                        `;
                    }

                    BootstrapDialog.show({
                        title: 'Update Product: ' + productName,
                        message: `<form id="editProductForm" enctype="multipart/form-data">
                          <div class="form-group">
                            <label>Product Name:</label>
                            <input type="text" class="form-control" name="product_name" value="${productName}" required>
                          </div>
                          <div class="form-group">
                            <label>Stock:</label>
                            <input type="number" class="form-control" name="stock" value="${productStock}" required>
                          </div>
                          <div class="form-group">
                            <label>Description:</label>
                            <textarea class="form-control" name="description" required>${productDescription}</textarea>
                          </div>
                          <div class="form-group">
                            <label>Suppliers:</label>
                            ${supplierOptions}
                          </div>
                          <div class="form-group">
                            <label>Product Image:</label>
                            <input type="file" class="form-control" name="img">
                            <img src="${productImage}" style="max-width: 100px; display: block; margin-top: 10px;">
                          </div>
                          <input type="hidden" name="pid" value="${productId}">
                        </form>`,
                        buttons: [{
                            label: 'Save',
                            cssClass: 'btn-primary',
                            action: function(dialogItself) {
                                const formData = new FormData(document.getElementById('editProductForm'));
                                
                                $.ajax({
                                    method: 'POST',
                                    data: formData,
                                    url: 'database/update-product.php',
                                    processData: false,
                                    contentType: false,
                                    dataType: 'json',
                                    success: function(data) {
                                        if (data.success) {
                                            BootstrapDialog.alert({
                                                type: BootstrapDialog.TYPE_SUCCESS,
                                                message: data.message,
                                                callback: function() {
                                                    location.reload();
                                                }
                                            });
                                        } else {
                                            BootstrapDialog.alert({
                                                type: BootstrapDialog.TYPE_DANGER,
                                                message: data.message,
                                            });
                                        }
                                    }
                                });
                                dialogItself.close();
                            }
                        }, {
                            label: 'Cancel',
                            action: function(dialogItself) {
                                dialogItself.close();
                            }
                        }]
                    });
                }
            });
        },

        this.initialize = function() {
            this.registerEvents();
        }
    }
    var script = new script;
    script.initialize();
</script>
</body>
</html>
