<div id="sidebar">
    <div class="sidebar-wrapper active">
        <div class="sidebar-header position-relative">
            <div class="d-flex justify-content-center align-items-center">
                <div class="logo d-flex">
                    <a href="/dashboard" class="sidebar-logo-text" style="font-size: 2rem; font-weight: bold; color: #333; text-decoration: none;">Accenix</a>
                </div>
            </div>
        </div>
        <div class="sidebar-menu">

            <ul class="menu">
                {{-- <li class="sidebar-title">Menu</li> --}}

                <li class="sidebar-item {{ Request::is('dashboard*') ? 'active' : '' }}">
                    <a href="/dashboard" class="sidebar-link">
                        <i class="bi bi-grid-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                {{-- <li class="sidebar-item active has-sub"> --}}
                    {{-- @if (in_array('admin', session('roles')) || in_array('superadmin', session('roles'))) --}} 
                        @if (!collect(session('roles', []))->contains('sales') || collect(session('roles', []))->contains('superadmin'))
                        <li
                            class="sidebar-item has-sub {{ Request::is('item*') || Request::is('uom*') || Request::is('price*') || Request::is('principal*') || Request::is('convert_uom*') || Request::is('customer*') || Request::is('coa*') || Request::is('gudang*') ? 'active' : '' }}">
                            <a href="#" class="sidebar-link">
                                <i class="bi bi bi-database"></i>
                                <span>Master Data</span>
                            </a>
                            <ul class="submenu active">
                                <li class="submenu-item has-sub submenu-item {{ Request::is('item*') ? 'active' : '' }}">
                                    <a href="#" class="submenu-link">Item</a>
                                    <ul class="submenu submenu-level-2 ">
                                        <li class="submenu-item {{ Request::is('item_type*') ? 'active' : '' }}">
                                            <a href="/item_type" class="submenu-link">Item Type</a>
                                        </li>
                                        <li class="submenu-item {{ Request::is('item_management*') ? 'active' : '' }}">
                                            <a href="/item_management" class="submenu-link">Item</a>
                                        </li>
                                    </ul>
                                </li>
                                <li
                                    class="submenu-item has-sub submenu-item {{ Request::is('uom*') || Request::is('convert_uom*') ? 'active' : '' }}">
                                    <a href="#" class="submenu-link">UOM</a>
                                    <ul class="submenu submenu-level-2 ">
                                        <li class="submenu-item {{ Request::is('uom*') ? 'active' : '' }}">
                                            <a href="/uom" class="submenu-link">UOM</a>
                                        </li>
                                        <li class="submenu-item {{ Request::is('convert_uom*') ? 'active' : '' }}">
                                            <a href="/convert_uom" class="submenu-link">Convert UOM</a>
                                        </li>
                                    </ul>
                                </li>

                                <li class="submenu-item {{ Request::is('price*') ? 'active' : '' }}">
                                    <a href="/price" class="submenu-link">Price</a>
                                </li>
                                <li class="submenu-item {{ Request::is('principal*') ? 'active' : '' }}">
                                    <a href="/principal" class="submenu-link">Principal</a>
                                </li>
                                <li class="submenu-item {{ Request::is('customer*') ? 'active' : '' }}">
                                    <a href="/customer" class="submenu-link">Customer</a>
                                </li>
                                <li class="submenu-item has-sub submenu-item {{ Request::is('coa*') ? 'active' : '' }}">
                                    <a href="#" class="submenu-link">COA</a>
                                    <ul class="submenu submenu-level-2 ">
                                        <li class="submenu-item {{ Request::is('coa_type*') ? 'active' : '' }}">
                                            <a href="/coa_type" class="submenu-link">COA Type</a>
                                        </li>
                                        <li class="submenu-item {{ Request::is('coa_management*') ? 'active' : '' }}">
                                            <a href="/coa_management" class="submenu-link">COA</a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="submenu-item {{ Request::is('gudang*') ? 'active' : '' }}">
                                    <a href="/gudang" class="submenu-link">Gudang</a>
                                </li>
                            </ul>
                        </li>
                        @endif
                    {{-- @endif --}}
                
                {{-- Administration Section - Superadmin & Sales only --}}
                @php
                    $userRoles = collect(session('user_info.roles', []));
                    $roleNames = $userRoles->map(function($role) {
                        return is_object($role) && isset($role->display) ? strtolower($role->display) : (is_array($role) && isset($role['display']) ? strtolower($role['display']) : strtolower($role));
                    });
                @endphp
                @if ($roleNames->contains('superadmin') || $roleNames->contains('sales'))
                <li class="sidebar-item has-sub {{ Request::is('administration/user*') || Request::is('administration/role*') || Request::is('administration/permission*') ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-gear-fill"></i>
                        <span>Administration</span>
                    </a>
                    <ul class="submenu active">
                        <li class="submenu-item {{ Request::is('administration/user*') ? 'active' : '' }}">
                            <a href="/administration/user" class="submenu-link">User</a>
                        </li>
                        <li class="submenu-item {{ Request::is('administration/role*') ? 'active' : '' }}">
                            <a href="/administration/role" class="submenu-link">Role</a>
                        </li>
                        <li class="submenu-item {{ Request::is('administration/permission*') ? 'active' : '' }}">
                            <a href="/administration/permission" class="submenu-link">Permission</a>
                        </li>
                    </ul>
                </li>
                @endif

                {{-- CRM Section - Superadmin & Sales only --}}
                @if (collect(session('roles', []))->map('strtolower')->contains(function ($role) {
                    return in_array($role, ['sales']);
                }))
                <li class="sidebar-item has-sub {{ Request::is('admin/crm*') ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-people-fill"></i>
                        <span>CRM</span>
                    </a>
                    <ul class="submenu active">
                        <li class="submenu-item {{ Request::is('admin/crm/dashboard*') ? 'active' : '' }}">
                            <a href="{{ route('admin.crm.dashboard') }}" class="submenu-link">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="submenu-item {{ Request::is('admin/crm/leads*') ? 'active' : '' }}">
                            <a href="{{ route('admin.crm.leads.index') }}" class="submenu-link">
                                <i class="bi bi-person-plus"></i> Lead Management
                            </a>
                        </li>
                        <li class="submenu-item {{ Request::is('admin/crm/organizations*') ? 'active' : '' }}">
                            <a href="{{ route('admin.crm.organizations.index') }}" class="submenu-link">
                                <i class="bi bi-building"></i> Organizations
                            </a>
                        </li>
                    </ul>
                </li>
                @endif

                @if (!collect(session('roles', []))->contains('sales') || collect(session('roles', []))->contains('superadmin'))
                <li
                    class="sidebar-item has-sub {{ Request::is('penerimaan_barang*') || Request::is('purchase_order*') || Request::is('dasar_pembelian*') || Request::is('rekap_po*') || Request::is('invoice_pembelian*') || Request::is('return_pembelian*') || Request::is('report_pembelian*') ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-truck"></i>
                        <span>Procurement</span>
                    </a>
                    <ul class="submenu active">
                        <li class="submenu-item {{ Request::is('purchase_order*') ? 'active' : '' }}">
                            <a href="/purchase_order" class="submenu-link">Purchase Order</a>
                        </li>
                        <li class="submenu-item {{ Request::is('penerimaan_barang*') ? 'active' : '' }}">
                            <a href="/penerimaan_barang" class="submenu-link">Penerimaan Barang</a>
                        </li>
                        <li class="submenu-item {{ Request::is('dasar_pembelian*') ? 'active' : '' }}">
                            <a href="/dasar_pembelian" class="submenu-link">Dasar Pembelian</a>
                        </li>
                        <li class="submenu-item {{ Request::is('invoice_pembelian*') ? 'active' : '' }}">
                            <a href="/invoice_pembelian" class="submenu-link">Invoice</a>
                        </li>
                        <li class="submenu-item {{ Request::is('rekap_*') ? 'active' : '' }}">
                            <a href="/rekap_po" class="submenu-link">Rekap PO</a>
                        </li>
                        <li class="submenu-item {{ Request::is('return_pembelian*') ? 'active' : '' }}">
                            <a href="/return_pembelian" class="submenu-link">Return</a>
                        </li>
                        <li class="submenu-item {{ Request::is('report_pembelian*') ? 'active' : '' }}">
                            <a href="/report_pembelian" class="submenu-link">Report</a>
                        </li>
                    </ul>
                </li>
                @endif
                @if (!collect(session('roles', []))->contains('sales') || collect(session('roles', []))->contains('superadmin'))
                <li
                    class="sidebar-item has-sub {{ Request::is('stock*') || Request::is('stock_gudang*') || Request::is('stock_in_transit*') || Request::is('transfer_stock*') || Request::is('report_persediaan*') ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-box-seam"></i>
                        <span>Inventory</span>
                    </a>
                    <ul class="submenu active">
                        <li class="submenu-item {{ Request::is('stock') || Request::is('stock/*') ? 'active' : '' }}">
                            <a href="/stock" class="submenu-link">Stock</a>
                        </li>
                    </ul>
                </li>
                @endif
                @if (!collect(session('roles', []))->contains('sales') || collect(session('roles', []))->contains('superadmin'))
                <li
                    class="sidebar-item has-sub {{ Request::is('penjualan*') || Request::is('range_price*') || Request::is('return_penjualan*') || Request::is('invoice_penjualan*') || Request::is('dasar_penjualan*') || Request::is('report_penjualan*') ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-cart-fill"></i>
                        <span>Penjualan</span>
                    </a>
                    <ul class="submenu active">
                        <li class="submenu-item {{ Request::is('penjualan*') ? 'active' : '' }}">
                            <a href="/penjualan" class="submenu-link">Penjualan</a>
                        </li>
                        <li class="submenu-item {{ Request::is('invoice_penjualan*') ? 'active' : '' }}">
                            <a href="/invoice_penjualan" class="submenu-link">Invoice</a>
                        </li>
                        <li class="submenu-item {{ Request::is('range_price*') ? 'active' : '' }}">
                            <a href="/range_price" class="submenu-link">Range Price Management</a>
                        </li>
                        <li class="submenu-item {{ Request::is('return_penjualan*') ? 'active' : '' }}">
                            <a href="/return_penjualan" class="submenu-link">Retur</a>
                        </li>
                        <li class="submenu-item {{ Request::is('dasar_penjualan*') ? 'active' : '' }}">
                            <a href="/dasar_penjualan" class="submenu-link">Dasar Penjualan</a>
                        </li>
                        <li class="submenu-item {{ Request::is('report_penjualan*') ? 'active' : '' }}">
                            <a href="/report_penjualan" class="submenu-link">Report</a>
                        </li>
                    </ul>
                </li>
                @endif
                @if (!collect(session('roles', []))->contains('sales') || collect(session('roles', []))->contains('superadmin'))
                <li
                    class="sidebar-item has-sub {{ (Request::is('hutang*') || Request::is('piutang*') ? 'active' : '' || Request::is('jurnal_pemasukan*') || Request::is('jurnal_pengeluaran*')) ? 'active' : '' }}">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-cash-stack"></i>
                        <span>Cash Bank</span>
                    </a>
                    <ul class="submenu active">
                        <li class="submenu-item {{ Request::is('hutang*') ? 'active' : '' }}">
                            <a href="/hutang" class="submenu-link">Hutang</a>
                        </li>
                        <li class="submenu-item {{ Request::is('piutang*') ? 'active' : '' }}">
                            <a href="/piutang" class="submenu-link">Piutang</a>
                        </li>
                        <li class="submenu-item {{ Request::is('jurnal_pemasukan*') ? 'active' : '' }}">
                            <a href="/jurnal_pemasukan" class="submenu-link">Pemasukan</a>
                        </li>
                        <li class="submenu-item {{ Request::is('jurnal_pengeluaran*') ? 'active' : '' }}">
                            <a href="/jurnal_pengeluaran" class="submenu-link">Pengeluaran</a>
                        </li>
                    </ul>
                </li>
                @endif
                @if (!collect(session('roles', []))->contains('sales') || collect(session('roles', []))->contains('superadmin'))
                <li
                    class="sidebar-item has-sub {{ (Request::is('jurnal_penyesuaian*') || Request::is('neraca*') ? 'active' : '' || Request::is('laba_rugi*') || Request::is('report_hutang*') || Request::is('asset*') || Request::is('report_piutang*') || Request::is('report_cash*')) ? 'active' : '' }} }} ">

                    <a href="#" class="sidebar-link">
                        <i class="bi bi-calculator"></i>
                        <span>Accounting</span>
                    </a>
                    <ul class="submenu active">
                        <li class="submenu-item {{ Request::is('jurnal_penyesuaian*') ? 'active' : '' }}">
                            <a href="/jurnal_penyesuaian" class="submenu-link">Jurnal Penyesuaian</a>
                        </li>
                        <li class="submenu-item {{ Request::is('asset*') ? 'active' : '' }}">
                            <a href="/asset" class="submenu-link">Asset</a>
                        </li>
                        <li class="submenu-item {{ Request::is('buku_besar_pembantu*') ? 'active' : '' }}">
                            <a href="/buku_besar_pembantu" class="submenu-link">Buku Besar Pembantu</a>
                        </li>
                        <li class="submenu-item {{ Request::is('laba_rugi*') ? 'active' : '' }}">
                            <a href="/laba_rugi" class="submenu-link">Laba Rugi</a>
                        </li>
                        <li class="submenu-item {{ Request::is('neraca*') ? 'active' : '' }}">
                            <a href="/neraca" class="submenu-link">Neraca</a>
                        </li>
                        <li class="submenu-item {{ Request::is('report_cash*') ? 'active' : '' }}">
                            <a href="/report_cash" class="submenu-link">Report Cash</a>
                        </li>
                        <li class="submenu-item {{ Request::is('report_hutang*') ? 'active' : '' }}">
                            <a href="/report_hutang" class="submenu-link">Report Hutang</a>
                        </li>
                        <li class="submenu-item {{ Request::is('report_piutang*') ? 'active' : '' }}">
                            <a href="/report_piutang" class="submenu-link">Report Piutang</a>
                        </li>
                    </ul>
                </li>
                @endif
            </ul>
        </div>
    </div>
</div>