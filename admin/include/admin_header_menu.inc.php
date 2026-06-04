<header class="main-header" style="position:fixed;width:100%">
    <!-- Logo -->
    <a href="/admin/member_list.php" class="logo">
        <!-- mini logo for sidebar mini 50x50 pixels -->
        <span class="logo-mini"><b>A</b>LT</span>
        <!-- logo for regular state AND mobile devices -->
        <span class="logo-lg"><b>셀링솔루션관리자</b></span>
    </a>
    <!-- Header Navbar: style can be found in header.less -->
    <nav class="navbar navbar-static-top" role="navigation">
        <!-- Sidebar toggle button-->
        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>
        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
                <!-- Messages: style can be found in dropdown.less-->
                <li class="dropdown messages-menu">
                    <div style="margin-top:8px;margin-right:10px;">
                        <button class="btn btn-block btn-info btn-flat" onclick="location='/ma.php';">홈페이지</button>
                    </div>
                </li>
                <li class="dropdown messages-menu">
                    <div style="margin-top:8px;margin-right:10px;">
                        <button class="btn btn-block btn-info btn-flat" onclick="window.open('about:blank').location.href='http://onlyonemall.net';">온리원쇼핑몰</button>
                    </div>
                </li>
                <li class="dropdown messages-menu">
                    <div style="margin-top:8px;margin-right:10px;">
                        <button class="btn btn-block btn-info btn-flat" onclick="window.open('about:blank').location.href='http://j.mp/33OwuGa';">온리원셀링강연회</button>
                    </div>
                </li>
                <li class="dropdown messages-menu">
                    <div style="margin-top:8px;margin-right:10px;">
                        <button class="btn btn-block btn-info btn-flat" onclick="location='/admin/admin_issue_dashboard.php';">이슈대시보드</button>
                    </div>
                </li>
                <li class="dropdown messages-menu">
                    <div style="margin-top:8px;margin-right:10px;">
                        <button class="btn btn-block btn-success btn-flat" onclick="location='/admin/admin_manual.php';" style="font-weight:700;">매뉴얼 관리</button>
                    </div>
                </li>
                <li class="dropdown messages-menu">
                    <div style="margin-top:8px;margin-right:10px;">
                        <button class="btn btn-block btn-info btn-flat"  onclick="location='/mypage.php';">마이페이지</button>
                    </div>
                </li>
                <!-- User Account: style can be found in dropdown.less -->
                <li class="dropdown user user-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <?=$member_1['mem_nick']?>님 환영합니다.
                    </a>
                </li>
                <!-- Control Sidebar Toggle Button -->
                <li>
                    <div style="margin-top:8px;margin-right:10px;">
                        <button class="btn btn-block btn-primary btn-flat" onclick="logout()">로그아웃</button>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</header>
