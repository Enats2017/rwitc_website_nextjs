"use client";
import { useEffect, useRef, useState } from "react";
import { UPLOAD_URL } from "../../../services/api";
import "./Hero.css";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation, Autoplay, EffectFade, Pagination, } from "swiper/modules";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/effect-fade";
import "swiper/css/pagination";
import { getBanners } from "../../../services/bannerService";

export default function Hero() {
  const prevRef = useRef(null);
  const nextRef = useRef(null);
  const [images, setImages] = useState([]);

  useEffect(() => {
    async function loadBanner() {
      const banners = await getBanners();
      setImages(banners);
    }
    loadBanner();
  }, []);

  return (
    <div className="heroWrapper">
      <section className="hero">
        <Swiper
          key={images.length}
          modules={[Navigation, Autoplay, EffectFade, Pagination,]}
          slidesPerView={1}
          loop={images.length > 1}
          // loop={true}
          speed={1000}
          navigation={{
            prevEl: prevRef.current,
            nextEl: nextRef.current,
          }}
          pagination={{
            clickable: true,
            el: ".heroPagination",
          }}
          onBeforeInit={(swiper) => {
            swiper.params.navigation.prevEl = prevRef.current;
            swiper.params.navigation.nextEl = nextRef.current;
          }}
          autoplay={{
            delay: 4000,
            disableOnInteraction: false,
          }}
          className="heroSwiper"
        >
          {
            images.map((item) => (
              <SwiperSlide key={item.id}>
                <div className="heroSlide">
                  <img
                    src={`${UPLOAD_URL}/${item.source}`}
                    alt={item.title}
                  />
                </div>
              </SwiperSlide>
            ))
          }
        </Swiper>
        <button ref={prevRef} className="heroNavBtn heroNavPrev">
          <svg viewBox="0 0 24 24" fill="none">
            <path
              d="M15 6L9 12L15 18"
              stroke="currentColor"
              strokeWidth="2.4"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
        </button>
        <button
          ref={nextRef}
          className="heroNavBtn heroNavNext"
        >
          <svg viewBox="0 0 24 24" fill="none">
            <path
              d="M9 6L15 12L9 18"
              stroke="currentColor"
              strokeWidth="2.4"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
        </button>
        <div className="heroPagination"></div>
      </section>
    </div>
  );
}