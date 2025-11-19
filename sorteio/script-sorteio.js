// Sorteio Infância Conectada JavaScript

const empresasData = {
    
    1: {
        id: 1,
        nome: 'Montreal',
        logo: '../images/montreal-images/logo-montreal.jpg',
        instagram: 'https://www.instagram.com/colaboradoresmontreal/',
        description: '<center><p style="font-size: 20px;">Vamos juntos nessa corrente do bem? <br>E aproveita pra conhecer a Montreal Araraquara – moda e casa com qualidade e preço que surpreendem!',
        links: [
            { type: 'website', label: 'Site Oficial', url: 'https://www.montrealmodaecasa.com.br/store/araraquara-sp-14801-120/14?srsltid=AfmBOoqlweg0JkZg_OTZck-xuric5T3FmfuOxAfcpzRb7uoxt0gPqeb_', icon: 'globe' },
            { type: 'instagram', label: 'Instagram', url: 'https://www.instagram.com/colaboradoresmontreal/', icon: 'instagram' },
            { type: 'facebook', label: 'Facebook', url: 'https://www.facebook.com/montrealmagazineararaquara/?locale=pt_BR', icon: 'facebook' },
        ],
        gallery: [
            '../images/montreal-images/montreal-1.jpg',
            '../images/montreal-images/montreal-2.jpg',
            '../images/montreal-images/montreal-3.jpg',
            '../images/montreal-images/montreal-4.jpg',
            '../images/montreal-images/montreal-5.jpg',
            '../images/montreal-images/montreal-6.jpg'
        ]
    },
    
    2: {
        id: 2,
        nome: 'Del Match',
        logo: '../images/delmatch-images/del-1.png',
        instagram: 'https://www.instagram.com/delmatchdeliveryoficial/',
        description: '<center><p style="font-size: 20px;">Vamos juntos nesse sorteio solidário?<br>E se você vai começar um negócio, conheça a Del Match Araraquara. A solução para suas entregas!',
        links: [
            { type: 'website', label: 'Site Oficial', url: 'https://delmatch.com.br', icon: 'globe' },
            { type: 'instagram', label: 'Instagram', url: 'https://www.instagram.com/delmatchdeliveryoficial/', icon: 'instagram' },
            { type: 'facebook', label: 'Facebook', url: 'https://www.facebook.com/delmatchdellivery/', icon: 'facebook' },
        ],
        gallery: [
            '../images/delmatch-images/del-7.png',
            '../images/delmatch-images/del-3.webp',
            '../images/delmatch-images/del-4.jpg',
            '../images/delmatch-images/del-5.jpg',
            '../images/delmatch-images/del-6.jpg',
            '../images/delmatch-images/del-2.webp'
        ]
    },
    
    3: {
        id: 3,
        nome: 'ST Motors',
        logo: '../images/stmotors-images/logo-st.png',
        instagram: 'https://instagram.com/stmotors_ara',
        description: '<center><p style="font-size: 20px;">Vamos juntos nesse sorteio solidário? <br> Siga a ST Motors no Insta e garanta sua vaga.<br> Duas rodas, boa causa e muita paixão por Araraquara!',
        links: [
            { type: 'website', label: 'Site Oficial', url: 'https://stmotorsara.com.br', icon: 'globe' },
            { type: 'instagram', label: 'Instagram', url: 'https://instagram.com/stmotors_ara/', icon: 'instagram' },
            { type: 'facebook', label: 'Facebook', url: 'https://facebook.com/St.Motors.Araraquara/', icon: 'facebook' },
        ],
        gallery: [
            '../images/stmotors-images/st1.png',
            '../images/stmotors-images/st2.jpg',
            '../images/stmotors-images/st3.png',
            '../images/stmotors-images/st4.png',
            '../images/stmotors-images/st5.png',
            '../images/stmotors-images/st6.png'
        ]
    },
    
    4: {
        id: 4,
        nome: 'Infância Conectada',
        logo: '../images/logo-infancia-conectada.png',
        instagram: 'https://www.instagram.com/infancia_conectada_/',
        description: '<center><p style="font-size: 20px;">Topa fazer parte de algo que transforma vidas? <br>Conheça o Infância Conectada – um projeto que leva inclusão digital para crianças em acolhimento!',
        links: [
            { type: 'website', label: 'Site Oficial', url: 'https://infanciaconectada.com.br', icon: 'globe' },
            { type: 'instagram', label: 'Instagram', url: 'https://www.instagram.com/infancia_conectada_/', icon: 'instagram' },
        ],
        gallery: [
            '../images/infancia-images/infancia-1.png',
            '../images/infancia-images/infancia-1.png',
            '../images/infancia-images/infancia-1.png',
            '../images/infancia-images/infancia-1.png',
            '../images/infancia-images/infancia-1.png',
            '../images/infancia-images/infancia-1.png'
        ]
    }
};

// Ícones SVG para os links
const iconsSVG = {
    globe: '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm7.5-6.923c-.67.204-1.335.82-1.887 1.855A7.97 7.97 0 0 0 5.145 4H7.5V1.077zM4.09 4a9.267 9.267 0 0 1 .64-1.539 6.7 6.7 0 0 1 .597-.933A7.025 7.025 0 0 0 2.255 4H4.09zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a6.958 6.958 0 0 0-.656 2.5h2.49zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5H4.847zM8.5 5v2.5h2.99a12.495 12.495 0 0 0-.337-2.5H8.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5H4.51zm3.99 0V11h2.653c.187-.765.306-1.608.338-2.5H8.5zM5.145 12c.138.386.295.744.468 1.068.552 1.035 1.218 1.65 1.887 1.855V12H5.145zm.182 2.472a6.696 6.696 0 0 1-.597-.933A9.268 9.268 0 0 1 4.09 12H2.255a7.024 7.024 0 0 0 3.072 2.472zM3.82 11a13.652 13.652 0 0 1-.312-2.5h-2.49c.062.89.291 1.733.656 2.5H3.82zm6.853 3.472A7.024 7.024 0 0 0 13.745 12H11.91a9.27 9.27 0 0 1-.64 1.539 6.688 6.688 0 0 1-.597.933zM8.5 12v2.923c.67-.204 1.335-.82 1.887-1.855.173-.324.33-.682.468-1.068H8.5zm3.68-1h2.146c.365-.767.594-1.61.656-2.5h-2.49a13.65 13.65 0 0 1-.312 2.5zm2.802-3.5a6.959 6.959 0 0 0-.656-2.5H12.18c.174.782.282 1.623.312 2.5h2.49zM11.27 2.461c.247.464.462.98.64 1.539h1.835a7.024 7.024 0 0 0-3.072-2.472c.218.284.418.598.597.933zM10.855 4a7.966 7.966 0 0 0-.468-1.068C9.835 1.897 9.17 1.282 8.5 1.077V4h2.355z"/></svg>',
    instagram: '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334z"/></svg>',
    facebook: '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z"/></svg>',
    
};

// Variável global para armazenar ID da sessão
let sessaoId = null;

// ========================================
// Proteção de acesso à página do sorteio
// ========================================
document.addEventListener("DOMContentLoaded", async () => {
    // Obter ID da sessão da URL
    const urlParams = new URLSearchParams(window.location.search);
    sessaoId = urlParams.get('s');

    if (!sessaoId) {
        alert("Você precisa se cadastrar antes de acessar o sorteio.");
        window.location.href = "https://infanciaconectada.com.br";
        return;
    }

    // Validar sessão no servidor
    try {
        const response = await fetch("https://infanciaconectada.com.br/sorteio/registrar.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ 
                acao: 'validar_sessao',
                sessao_id: sessaoId 
            })
        });

        const result = await response.json();

        if (result.status !== 'ok') {
            alert("Sessão inválida ou expirada. Faça o cadastro novamente.");
            window.location.href = "https://infanciaconectada.com.br";
            return;
        }

        // Sessão válida, inicializa a página
        inicializarPagina();
    } catch (error) {
        console.error("Erro ao validar sessão:", error);
        alert("Erro ao validar sua sessão. Tente novamente.");
        window.location.href = "https://infanciaconectada.com.br";
    }
});

function inicializarPagina() {
    const empresaCards = document.querySelectorAll('.empresa-card');
    const modalOverlay = document.getElementById('modalOverlay');
    const modalClose = document.getElementById('modalClose');
    const modalBg = document.getElementById('modalBg');
    const modalLogoImg = document.getElementById('modalLogoImg');
    const modalCta = document.getElementById('modalCta');
    const modalDescription = document.getElementById('modalDescription');
    const modalCompanyTitle = document.getElementById('modalCompanyTitle');
    const companyLinks = document.getElementById('companyLinks');
    const galleryGrid = document.getElementById('galleryGrid');

    // Event listeners para os cards
    empresaCards.forEach(card => {
        card.addEventListener('click', function () {
            const empresaId = parseInt(this.getAttribute('data-empresa'));
            openModal(empresaId);
        });

        card.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const empresaId = parseInt(this.getAttribute('data-empresa'));
                openModal(empresaId);
            }
        });
    });

    // Fechar modal
    modalClose.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', function (e) {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalOverlay.classList.contains('active')) {
            closeModal();
        }
    });

    // Função para abrir modal
    function openModal(empresaId) {
        const empresa = empresasData[empresaId];

        if (!empresa) {
            console.error('Empresa não encontrada:', empresaId);
            return;
        }

        modalBg.style.backgroundImage = `url('${empresa.bgImage || ''}')`;
        modalLogoImg.src = empresa.logo;
        modalLogoImg.alt = `Logo ${empresa.nome}`;
        modalCta.href = empresa.instagram;
        modalCta.dataset.empresa = empresaId;
        modalDescription.innerHTML = empresa.description;
        modalCompanyTitle.textContent = `Conheça a ${empresa.nome}`;

        companyLinks.innerHTML = '';
        empresa.links.forEach(link => {
            const linkElement = document.createElement('a');
            linkElement.href = link.url;
            linkElement.className = 'company-link';
            linkElement.target = '_blank';
            linkElement.rel = 'noopener noreferrer';
            linkElement.innerHTML = `${iconsSVG[link.icon] || iconsSVG.globe} ${link.label}`;
            companyLinks.appendChild(linkElement);
        });

        galleryGrid.innerHTML = '';
        empresa.gallery.forEach((imgUrl, index) => {
            const galleryItem = document.createElement('div');
            galleryItem.className = 'gallery-item';
            galleryItem.innerHTML = `<img src="${imgUrl}" alt="Galeria ${empresa.nome}">`;
            galleryItem.addEventListener('click', (e) => {
                e.stopPropagation();
                openLightbox(empresa.gallery, index);
            });
            galleryGrid.appendChild(galleryItem);
        });

        modalOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            modalClose.focus();
        }, 100);
    }

    function closeModal() {
        modalOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    function openLightbox(images, startIndex) {
        const lightbox = document.createElement('div');
        lightbox.className = 'lightbox-overlay';
        lightbox.innerHTML = `
            <button class="lightbox-close" aria-label="Fechar galeria">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                </svg>
            </button>
            <button class="lightbox-prev" aria-label="Imagem anterior">
                <svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                </svg>
            </button>
            <button class="lightbox-next" aria-label="Próxima imagem">
                <svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                </svg>
            </button>
            <div class="lightbox-content">
                <div class="lightbox-image-container">
                    <img src="" alt="" class="lightbox-image">
                </div>
                <div class="lightbox-counter"></div>
            </div>
        `;

        document.body.appendChild(lightbox);
        document.body.style.overflow = 'hidden';

        let currentIndex = startIndex;
        const lightboxImg = lightbox.querySelector('.lightbox-image');
        const lightboxCounter = lightbox.querySelector('.lightbox-counter');
        const closeBtn = lightbox.querySelector('.lightbox-close');
        const prevBtn = lightbox.querySelector('.lightbox-prev');
        const nextBtn = lightbox.querySelector('.lightbox-next');

        function updateImage() {
            lightboxImg.src = images[currentIndex];
            lightboxCounter.textContent = `${currentIndex + 1} / ${images.length}`;

            prevBtn.style.opacity = currentIndex === 0 ? '0.5' : '1';
            prevBtn.style.pointerEvents = currentIndex === 0 ? 'none' : 'auto';

            nextBtn.style.opacity = currentIndex === images.length - 1 ? '0.5' : '1';
            nextBtn.style.pointerEvents = currentIndex === images.length - 1 ? 'none' : 'auto';
        }

        function closeLightbox() {
            lightbox.classList.remove('active');
            setTimeout(() => {
                document.body.removeChild(lightbox);
                document.body.style.overflow = '';
            }, 300);
        }

        function showPrev() {
            if (currentIndex > 0) {
                currentIndex--;
                updateImage();
            }
        }

        function showNext() {
            if (currentIndex < images.length - 1) {
                currentIndex++;
                updateImage();
            }
        }

        closeBtn.addEventListener('click', closeLightbox);
        prevBtn.addEventListener('click', showPrev);
        nextBtn.addEventListener('click', showNext);

        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });

        const handleKeydown = (e) => {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') showPrev();
            if (e.key === 'ArrowRight') showNext();
        };
        document.addEventListener('keydown', handleKeydown);

        let touchStartX = 0;
        let touchEndX = 0;

        lightboxImg.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });

        lightboxImg.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });

        function handleSwipe() {
            const swipeThreshold = 50;
            if (touchStartX - touchEndX > swipeThreshold) {
                showNext();
            } else if (touchEndX - touchStartX > swipeThreshold) {
                showPrev();
            }
        }

        const originalClose = closeLightbox;
        closeLightbox = function () {
            document.removeEventListener('keydown', handleKeydown);
            originalClose();
        };

        updateImage();
        setTimeout(() => lightbox.classList.add('active'), 10);
    }

    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    empresaCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'button');
        card.setAttribute('aria-label', `Ver detalhes de ${card.querySelector('.empresa-nome').textContent}`);
        observer.observe(card);
    });

    // Handler do botão CTA
    modalCta.addEventListener("click", async (e) => {
        e.preventDefault();

        if (!sessaoId) {
            alert("Sessão inválida. Por favor, cadastre-se novamente.");
            window.location.href = "https://infanciaconectada.com.br";
            return;
        }

        const empresaId = parseInt(modalCta.dataset.empresa);
        const instagramUrl = modalCta.href;

        // Desabilita o botão temporariamente
        const textoOriginal = modalCta.innerHTML;
        modalCta.style.pointerEvents = 'none';
        modalCta.style.opacity = '0.6';

        try {
            const response = await fetch("https://infanciaconectada.com.br/sorteio/registrar.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    acao: 'registrar_participacao',
                    sessao_id: sessaoId,
                    empresa: empresaId
                })
            });

            const result = await response.json();

            if (result.status === 'ok') {
                // Abre o Instagram
                window.open(instagramUrl, "_blank");
                
                // Feedback visual
                modalCta.innerHTML = `
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                    </svg>
                    Participação Registrada!
                `;
            } else if (result.status === 'duplicado') {
                alert("Você já participou do sorteio com essa empresa!");
                window.open(instagramUrl, "_blank");
            } else {
                throw new Error(result.mensagem || "Erro ao registrar");
            }
        } catch (error) {
            console.error("Erro:", error);
            alert("Erro ao registrar participação, mas o Instagram será aberto.");
            window.open(instagramUrl, "_blank");
        } finally {
            setTimeout(() => {
                modalCta.innerHTML = textoOriginal;
                modalCta.style.pointerEvents = 'auto';
                modalCta.style.opacity = '1';
            }, 3000);
        }
    });

    console.log('Sorteio Infância Conectada carregado!');
    console.log('Empresas cadastradas:', Object.keys(empresasData).length);
}