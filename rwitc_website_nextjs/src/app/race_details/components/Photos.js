"use client";

import { useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import {
    FaArrowRight,
    FaTimes,
    FaChevronUp,
} from "react-icons/fa";
import { GiHorseHead } from "react-icons/gi";
import { getPhotoGallery } from "../../../services/photoGalleryService";
import "./Photos.css";

// Max photos shown on the main grid before "View All" kicks in.
const MAX_VISIBLE = 9;

// When total photos is this many or fewer, show them all
// in one uniform-size grid instead of the big + small layout.
const UNIFORM_THRESHOLD = 4;

function toInputDate(dateStr) {
    if (!dateStr) {
        return new Date().toISOString().slice(0, 10);
    }

    const d = new Date(dateStr);

    if (isNaN(d)) {
        return new Date().toISOString().slice(0, 10);
    }

    return d.toISOString().slice(0, 10);
}

function toDisplayDate(dateStr) {
    if (!dateStr) return "";

    const d = new Date(dateStr);

    if (isNaN(d)) return "";

    return d
        .toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
        })
        .toUpperCase()
        .replace(/ /g, " ");
}

export default function Photos() {
    const router = useRouter();
    const searchParams = useSearchParams();

    const dateParam = searchParams.get("date");

    /* ================= STATE ================= */

    const [inputDate, setInputDate] = useState(
        toInputDate(dateParam)
    );

    const [zoomedImage, setZoomedImage] = useState(null);

    // Stores the index of the currently opened image
    const [zoomedIndex, setZoomedIndex] = useState(null);

    const [showAll, setShowAll] = useState(false);

    const [loading, setLoading] = useState(true);

    const [photos, setPhotos] = useState([]);

    const [raceDate, setRaceDate] = useState(
        dateParam || null
    );

    /* ================= LOAD PHOTOS ================= */

    useEffect(() => {
        let cancelled = false;

        async function loadPhotos() {
            setLoading(true);
            setShowAll(false);

            try {
                const result = await getPhotoGallery(dateParam);

                if (cancelled) return;

                setPhotos(result.images || []);

                setRaceDate(
                    result.raceDate || dateParam
                );
            } catch (error) {
                console.error(
                    "Failed to load photos:",
                    error
                );

                if (!cancelled) {
                    setPhotos([]);
                    setRaceDate(dateParam || null);
                }
            } finally {
                if (!cancelled) {
                    setLoading(false);
                }
            }
        }

        loadPhotos();

        return () => {
            cancelled = true;
        };
    }, [dateParam]);

    /* ================= PHOTO DATA ================= */

    const isUniform =
        photos.length <= UNIFORM_THRESHOLD;

    const hasMore =
        photos.length > MAX_VISIBLE;

    const gridPhotos =
        photos.slice(0, MAX_VISIBLE);

    const extraPhotos =
        hasMore
            ? photos.slice(MAX_VISIBLE)
            : [];

    const mainPhoto =
        !isUniform
            ? gridPhotos[0]
            : null;

    const thumbPhotos =
        !isUniform
            ? gridPhotos.slice(1)
            : gridPhotos;

    /* ================= SEARCH ================= */

    const handleSearch = () => {
        const params =
            new URLSearchParams(
                searchParams.toString()
            );

        params.set("type", "photos");
        params.set("date", inputDate);

        router.push(
            `/race_details?${params.toString()}`
        );
    };

    /* ================= OPEN IMAGE ================= */

    const openImage = (url, index) => {
        setZoomedImage(url);
        setZoomedIndex(index);

        // Prevent background page from scrolling
        document.body.style.overflow = "hidden";
    };

    /* ================= CLOSE LIGHTBOX ================= */

    const closeLightbox = () => {
        setZoomedImage(null);
        setZoomedIndex(null);

        // Allow page scrolling again
        document.body.style.overflow = "";
    };

    /* ================= PREVIOUS IMAGE ================= */

    const showPrevious = (e) => {
        e.stopPropagation();

        if (
            zoomedIndex === null ||
            photos.length === 0
        ) {
            return;
        }

        const newIndex =
            zoomedIndex === 0
                ? photos.length - 1
                : zoomedIndex - 1;

        setZoomedIndex(newIndex);
        setZoomedImage(
            photos[newIndex].url
        );
    };

    /* ================= NEXT IMAGE ================= */

    const showNext = (e) => {
        e.stopPropagation();

        if (
            zoomedIndex === null ||
            photos.length === 0
        ) {
            return;
        }

        const newIndex =
            zoomedIndex === photos.length - 1
                ? 0
                : zoomedIndex + 1;

        setZoomedIndex(newIndex);
        setZoomedImage(
            photos[newIndex].url
        );
    };

    /* ================= KEYBOARD CONTROL ================= */

    useEffect(() => {
        if (!zoomedImage) return;

        const handleKeyDown = (e) => {
            if (e.key === "Escape") {
                closeLightbox();
            }

            if (e.key === "ArrowLeft") {
                showPrevious(e);
            }

            if (e.key === "ArrowRight") {
                showNext(e);
            }
        };

        window.addEventListener(
            "keydown",
            handleKeyDown
        );

        return () => {
            window.removeEventListener(
                "keydown",
                handleKeyDown
            );
        };
    }, [zoomedImage, zoomedIndex, photos]);

    /* ================= CLEANUP SCROLL ================= */

    useEffect(() => {
        return () => {
            document.body.style.overflow = "";
        };
    }, []);

    /* ================= JSX ================= */

    return (
        <section className="photosSection">

            <div className="photosSectionBg" />

            {/* ================= HEADER ================= */}

            <div className="photosHeader">

                <p className="photosLabel">
                    Photos For Race Day
                </p>

                <h1 className="photosDate">
                    {toDisplayDate(raceDate)}
                </h1>

                <div className="photosDivider">

                    <span className="dividerLine" />

                    <GiHorseHead className="horseIcon" />

                    <span className="dividerLine" />

                </div>

                {/* ================= SEARCH ================= */}

                <div className="photosSearchBar">

                    <div className="dateInputWrap">

                        <input
                            type="date"
                            className="dateInput"
                            value={inputDate}
                            onChange={(e) =>
                                setInputDate(
                                    e.target.value
                                )
                            }
                        />

                    </div>

                    <button
                        type="button"
                        className="searchBtn"
                        onClick={handleSearch}
                    >
                        Search
                    </button>

                </div>

            </div>

            {/* ================= LOADING ================= */}

            {loading ? (

                <div className="noPhotos">
                    Loading photos...
                </div>

            ) : photos.length === 0 ? (

                <div className="noPhotos">
                    No photos available for this race day.
                </div>

            ) : (

                <>

                    {/* ================= UNIFORM GRID ================= */}

                    {isUniform ? (

                        <div className="photosGrid photosGridUniform">

                            {gridPhotos.map(
                                (photo, index) => (

                                    <div
                                        className="photoUniform"
                                        key={photo.id}
                                        style={{
                                            backgroundImage:
                                                `url(${photo.url})`,
                                        }}
                                        onClick={() =>
                                            openImage(
                                                photo.url,
                                                index
                                            )
                                        }
                                    />

                                )
                            )}

                        </div>

                    ) : (

                        /* ================= MAIN + THUMBNAILS ================= */

                        <div className="photosGrid">

                            {/* MAIN PHOTO */}

                            {mainPhoto && (

                                <div
                                    className="photoMain"
                                    style={{
                                        backgroundImage:
                                            `url(${mainPhoto.url})`,
                                    }}
                                    onClick={() =>
                                        openImage(
                                            mainPhoto.url,
                                            0
                                        )
                                    }
                                />

                            )}

                            {/* THUMBNAILS */}

                            {thumbPhotos.length > 0 && (

                                <div className="photoThumbGrid">

                                    {thumbPhotos.map(
                                        (photo, index) => (

                                            <div
                                                className="photoThumb"
                                                key={photo.id}
                                                style={{
                                                    backgroundImage:
                                                        `url(${photo.url})`,
                                                }}
                                                onClick={() =>
                                                    openImage(
                                                        photo.url,
                                                        index + 1
                                                    )
                                                }
                                            />

                                        )
                                    )}

                                </div>

                            )}

                        </div>

                    )}

                    {/* ================= EXTRA PHOTOS ================= */}

                    {showAll &&
                        extraPhotos.length > 0 && (

                            <div className="photosGrid photosGridUniform extraPhotosGrid">

                                {extraPhotos.map(
                                    (photo, index) => (

                                        <div
                                            className="photoUniform"
                                            key={photo.id}
                                            style={{
                                                backgroundImage:
                                                    `url(${photo.url})`,
                                            }}
                                            onClick={() =>
                                                openImage(
                                                    photo.url,
                                                    MAX_VISIBLE +
                                                        index
                                                )
                                            }
                                        />

                                    )
                                )}

                            </div>

                        )}

                    <p className="photoHint">
                        Click any image to see a larger preview.
                    </p>

                </>

            )}

            {/* ================= VIEW ALL ================= */}

            {hasMore && (

                <div className="viewAllWrap">

                    <button
                        type="button"
                        className="viewAllBtn"
                        onClick={() =>
                            setShowAll(
                                (prev) => !prev
                            )
                        }
                    >

                        {showAll ? (

                            <>
                                Show Less
                                <FaChevronUp />
                            </>

                        ) : (

                            <>
                                View All Photos
                                <FaArrowRight />
                            </>

                        )}

                    </button>

                </div>

            )}

            {/* =====================================================
                FULL SCREEN LIGHTBOX
            ===================================================== */}

            {zoomedImage && (

                <div
                    className="photoLightbox"
                    onClick={closeLightbox}
                >

                    {/* ================= CLOSE ================= */}

                    <button
                        type="button"
                        className="lightboxClose"
                        onClick={(e) => {
                            e.stopPropagation();
                            closeLightbox();
                        }}
                        aria-label="Close"
                    >
                        <FaTimes />
                    </button>

                    {/* ================= PREVIOUS ================= */}

                    <button
                        type="button"
                        className="lightboxPrev"
                        onClick={showPrevious}
                        aria-label="Previous image"
                    >
                        &#10094;
                    </button>

                    {/* ================= IMAGE ================= */}

                    <img
                        src={zoomedImage}
                        alt="Zoomed race day"
                        className="lightboxImg"
                        onClick={(e) =>
                            e.stopPropagation()
                        }
                    />

                    {/* ================= NEXT ================= */}

                    <button
                        type="button"
                        className="lightboxNext"
                        onClick={showNext}
                        aria-label="Next image"
                    >
                        &#10095;
                    </button>

                </div>

            )}

        </section>
    );
}