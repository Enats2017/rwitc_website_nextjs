"use client";
import { useEffect, useRef, useState } from "react";
import { UPLOAD_URL } from "../../../services/api";
import "./Hero.css";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation, Autoplay, Pagination } from "swiper/modules";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";
import { getBanners } from "../../../services/bannerService";

export default function Hero() {
  const prevRef = useRef(null);
  const nextRef = useRef(null);
  const swiperRef = useRef(null);
  const [images, setImages] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadBanner() {
      try {
        const banners = await getBanners();
        setImages(banners || []);
      } catch (e) {
        console.error("Banner load failed", e);
      } finally {
        setLoading(false);
      }
    }
    loadBanner();
  }, []);

  const handleImgLoad = () => swiperRef.current?.updateAutoHeight(0);

  return (
    <div className="heroWrapper">
      <section className={`hero ${loading || images.length === 0 ? "heroPlaceholder" : ""}`}>
        <Swiper
          key={images.length}
          modules={[Navigation, Autoplay, Pagination]}
          onSwiper={(s) => (swiperRef.current = s)}
          autoHeight={true}
          slidesPerView={1}
          loop={images.length > 1}
          speed={1000}
          navigation={{ prevEl: prevRef.current, nextEl: nextRef.current }}
          pagination={{ clickable: true, el: ".heroPagination" }}
          onBeforeInit={(swiper) => {
            swiper.params.navigation.prevEl = prevRef.current;
            swiper.params.navigation.nextEl = nextRef.current;
          }}
          autoplay={{ delay: 4000, disableOnInteraction: false }}
          className="heroSwiper"
        >
          {images.map((item, index) => {
            const src = item.source.startsWith("http") ? item.source : `${UPLOAD_URL}/${item.source}`;
            return (
              <SwiperSlide key={item.id}>
                <div className="heroSlide">
                  <img
                    src={src}
                    alt={item.title || "Banner"}
                    loading={index === 0 ? "eager" : "lazy"}
                    fetchPriority={index === 0 ? "high" : "auto"}
                    decoding="async"
                    onLoad={handleImgLoad}
                  />
                </div>
              </SwiperSlide>
            );
          })}
        </Swiper>

        <button ref={prevRef} className="heroNavBtn heroNavPrev" aria-label="Previous slide">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M15 6L9 12L15 18" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </button>
        <button ref={nextRef} className="heroNavBtn heroNavNext" aria-label="Next slide">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M9 6L15 12L9 18" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </button>

        <div className="heroPagination"></div>
      </section>
    </div>
  );
}