    </main>
    
    <!-- Footer -->
    <footer class="bg-black border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- About -->
                <div>
                    <div class="flex items-center mb-4">
                        <i class="fas fa-rocket text-2xl text-purple-400 mr-3"></i>
                        <h3 class="text-xl font-bold text-white">한국AI코딩허브협회</h3>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        AI코딩으로 일하고, 연결되고, 수익을 만드는 대한민국 AI코딩 허브 생태계의 중심
                    </p>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h4 class="text-white font-semibold mb-4">빠른 링크</h4>
                    <ul class="space-y-2">
                        <li>
                            <a href="/?page=about" class="text-gray-400 hover:text-purple-400 text-sm transition-colors">
                                협회소개
                            </a>
                        </li>
                        <li>
                            <a href="/?page=business" class="text-gray-400 hover:text-purple-400 text-sm transition-colors">
                                사업안내
                            </a>
                        </li>
                        <li>
                            <a href="/?page=platform" class="text-gray-400 hover:text-purple-400 text-sm transition-colors">
                                허브플랫폼
                            </a>
                        </li>
                        <li>
                            <a href="/?page=festival" class="text-gray-400 hover:text-purple-400 text-sm transition-colors">
                                AI코딩 페스티벌
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Services -->
                <div>
                    <h4 class="text-white font-semibold mb-4">서비스</h4>
                    <ul class="space-y-2">
                        <li>
                            <a href="/?page=platform" class="text-gray-400 hover:text-purple-400 text-sm transition-colors">
                                프로젝트 매칭
                            </a>
                        </li>
                        <li>
                            <a href="/?page=platform" class="text-gray-400 hover:text-purple-400 text-sm transition-colors">
                                개발자 등록
                            </a>
                        </li>
                        <li>
                            <a href="/?page=business" class="text-gray-400 hover:text-purple-400 text-sm transition-colors">
                                교육 프로그램
                            </a>
                        </li>
                        <li>
                            <a href="/?page=business" class="text-gray-400 hover:text-purple-400 text-sm transition-colors">
                                인증 시스템
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Contact -->
                <div>
                    <h4 class="text-white font-semibold mb-4">문의하기</h4>
                    <ul class="space-y-2">
                        <li class="flex items-center text-gray-400 text-sm">
                            <i class="fas fa-envelope text-purple-400 mr-2"></i>
                            contact@open.kiam.kr
                        </li>
                        <li class="flex items-center text-gray-400 text-sm">
                            <i class="fas fa-globe text-purple-400 mr-2"></i>
                            https://open.kiam.kr
                        </li>
                        <li>
                            <a href="/?page=contact" class="inline-block mt-2 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all">
                                문의하기
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Bottom Bar -->
            <div class="mt-12 pt-8 border-t border-gray-800">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-500 text-sm">
                        &copy; 2025 한국AI코딩허브협회. All rights reserved.
                    </p>
                    <div class="flex space-x-6 mt-4 md:mt-0">
                        <a href="#" class="text-gray-500 hover:text-purple-400 transition-colors">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-500 hover:text-purple-400 transition-colors">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-500 hover:text-purple-400 transition-colors">
                            <i class="fab fa-linkedin text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-500 hover:text-purple-400 transition-colors">
                            <i class="fab fa-youtube text-xl"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- AOS Init -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
        
        // Mobile Menu Toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('show');
        });
        
        // Scroll Effect for Nav
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
