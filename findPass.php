<? include_once $_SERVER["DOCUMENT_ROOT"]."/midnight/inc/inch.php"; ?>
<?
if(AuthUtil::isLoggedIn()){
    echo "<script>alert('비정상적인 접근입니다.'); history.back();</script>";
}
?>
    <script>
        $(document).ready(function(){

            $(".jLogin").click(function(){
                var email = $(".jEmailTxt").val();
                if(email == ""){
                    alert("회원 정보를 입력하세요.");
                    return;
                }

                sendPasswordReset(email);
            });
        });
    </script>

    <!--================Home Banner Area =================-->
        <section class="banner_area">
            <div class="banner_inner d-flex align-items-center">
            	<div class="overlay bg-parallax" data-stellar-ratio="0.9" data-stellar-vertical-offset="0" data-background=""></div>
				<div class="container">
					<div class="banner_content text-center">
						<div class="page_link">
							<a href="#">홈</a>
							<a href="#">마이페이지</a>
						</div>
						<h2>로그인</h2>
					</div>
				</div>
            </div>
        </section>
        <!--================End Home Banner Area =================-->
        
        <!--================Contact Area =================-->
        <section class="contact_area p_120">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="contact_info">
                            <div class="info_item">
                                <i class="lnr lnr-user"></i>
                                <h6><a href="#">패스워드 재설정</a></h6>
                                <p>패스워드 재설정 메일을 아래 입력된 주소로 발송합니다.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <form class="row contact_form" action="contact_process.php" method="post" id="contactForm" novalidate="novalidate">
                            <div class="col-md-12 text-center">
                                <div class="form-group">
                                    <input type="email" class="form-control jEmailTxt" id="account" name="account" placeholder="이메일">
                                </div>
                            </div>
                            <div class="col-md-12 text-right">
                                <button type="button" class="btn submit_btn jLogin">패스워드 재설정 메일 발송</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <!--================Contact Area =================-->
<? include_once $_SERVER["DOCUMENT_ROOT"]."/midnight/inc/incf.php"; ?>