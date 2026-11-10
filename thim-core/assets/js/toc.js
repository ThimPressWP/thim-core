(function ($) {
    "use strict";

    $(document).ready(function () {
        function add_heading_id_toc() {
            if (!document.querySelector('.thim-core-toc')) return;
            const slugify = text =>
                text.toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/đ/g, "d")
                .replace(/\./g, '-')
                .replace(/[^a-z0-9\s-]/g, "")
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-+|-+$/g, '');

            document.querySelectorAll('h2, h3, h4, h5, h6:not([id])').forEach(h => {
                h.id = slugify(h.textContent.trim());
            });
        }

        function add_active_class_toc() {
            const tocLinks = document.querySelectorAll('.thim-core-toc a');

            if (!tocLinks.length) {
                return;
            }

            const headings = Array.from(tocLinks)
                .map(link => {
                    const id = link.getAttribute('href')?.replace('#', '');
                    return document.getElementById(id);
                })
                .filter(Boolean);

            let activeIndex = -1;

            function setActive(index) {
                if (index === activeIndex || !tocLinks[index]) {
                    return;
                }

                tocLinks[activeIndex]?.classList.remove('active');
                tocLinks[index].classList.add('active');

                activeIndex = index;
            }

            function onScroll() {
                let currentIndex = 0;

                for (let i = headings.length - 1; i >= 0; i--) {
                    if (headings[i].getBoundingClientRect().top <= 100) {
                        currentIndex = i;
                        break;
                    }
                }

                setActive(currentIndex);
            }

            tocLinks.forEach((link, index) => {
                link.addEventListener('click', () => {
                    setActive(index);
                });
            });

            onScroll();
            
            window.addEventListener('scroll', onScroll, {
                passive: true
            });
        }

        function mobile_display_toc() {
            $('.thim-core-toc-toggle').on('click', function() {
                var toc = $(this).next();

                $(this).parent().toggleClass('toggle');
                toc.toggleClass('active');
            });

            $(document).on('click', '.thim-core-toc a', function() {
                var toc = $(this).closest('.thim-core-toc');
                toc.removeClass('active');
                toc.parent().removeClass('toggle');
            });

            $(document).on('click', function(e) {
                const $target = $(e.target);

                if (! $target.closest('.thim-core-toc').length && ! $target.closest('.thim-core-toc-toggle').length) {
                    $('.thim-core-toc').removeClass('active');
                    $('.thim-core-toc-wrapper').removeClass('toggle');
                }
            });
        }

        add_heading_id_toc();
        add_active_class_toc();
        mobile_display_toc();
    });

})(jQuery);
