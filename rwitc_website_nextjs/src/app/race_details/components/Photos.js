"use client";

import { useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { FaArrowRight, FaTimes, FaChevronUp } from "react-icons/fa";
import { GiHorseHead } from "react-icons/gi";
import { getPhotoGallery } from "../../../services/photoGalleryService";
import "./Photos.css";

// Max photos shown on the main grid before "View All" kicks in.
const MAX_VISIBLE = 9;
// When total photos is this many or fewer, show them all in one
// uniform-size grid instead of the big+small layout.
const UNIFORM_THRESHOLD = 4;

function toInputDate(dateStr) {
    if (!dateStr) return new Date().toISOString().slice(0, 10);
    const d = new Date(dateStr);
    if (isNaN(d)) return new Date().toISOString().slice(0, 10);
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

    const [inputDate, setInputDate] = useState(toInputDate(dateParam));
    const [zoomedImage, setZoomedImage] = useState(null);
    const [showAll, setShowAll] = useState(false);

    const [loading, setLoading] = useState(true);
    const [photos, setPhotos] = useState([]);
    const [raceDate, setRaceDate] = useState(dateParam || null);

    useEffect(() => {

        let cancelled = false;

        async function loadPhotos() {

            setLoading(true);
            setShowAll(false);

            const result = await getPhotoGallery(dateParam);

            if (cancelled) return;

            setPhotos(result.images);
            setRaceDate(result.raceDate || dateParam);
            setLoading(false);

        }

        loadPhotos();

        return () => {
            cancelled = true;
        };

    }, [dateParam]);

    const isUniform = photos.length <= UNIFORM_THRESHOLD;
    const hasMore = photos.length > MAX_VISIBLE;

    const gridPhotos = photos.slice(0, MAX_VISIBLE);
    const extraPhotos = hasMore ? photos.slice(MAX_VISIBLE) : [];

    const mainPhoto = !isUniform ? gridPhotos[0] : null;
    const thumbPhotos = !isUniform ? gridPhotos.slice(1) : gridPhotos;

    const handleSearch = () => {
        const params = new URLSearchParams(searchParams.toString());
        params.set("type", "photos");
        params.set("date", inputDate);
        router.push(`/race_details?${params.toString()}`);
    };

    const openImage = (url) => setZoomedImage(url);

    return (
        <section className="photosSection">
            <div className="photosSectionBg" />

            <div className="photosHeader">
                <p className="photosLabel">Photos For Race Day</p>
                <h1 className="photosDate">{toDisplayDate(raceDate)}</h1>
                <div className="photosDivider">
                    <span className="dividerLine" />
                    <GiHorseHead className="horseIcon" />
                    <span className="dividerLine" />
                </div>

                <div className="photosSearchBar">
                    <div className="dateInputWrap">
                        <input
                            type="date"
                            className="dateInput"
                            value={inputDate}
                            onChange={(e) => setInputDate(e.target.value)}
                        />
                    </div>
                    <button type="button" className="searchBtn" onClick={handleSearch}>
                        Search
                    </button>
                </div>
            </div>

            {loading ? (
                <div className="noPhotos">Loading photos...</div>
            ) : photos.length === 0 ? (
                <div className="noPhotos">No photos available for this race day.</div>
            ) : (
                <>
                    {isUniform ? (
                        <div className="photosGrid photosGridUniform">
                            {gridPhotos.map((photo) => (
                                <div
                                    className="photoUniform"
                                    key={photo.id}
                                    style={{ backgroundImage: `url(${photo.url})` }}
                                    onClick={() => openImage(photo.url)}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="photosGrid">
                            {mainPhoto && (
                                <div
                                    className="photoMain"
                                    style={{ backgroundImage: `url(${mainPhoto.url})` }}
                                    onClick={() => openImage(mainPhoto.url)}
                                />
                            )}

                            {thumbPhotos.length > 0 && (
                                <div className="photoThumbGrid">
                                    {thumbPhotos.map((photo) => (
                                        <div
                                            className="photoThumb"
                                            key={photo.id}
                                            style={{ backgroundImage: `url(${photo.url})` }}
                                            onClick={() => openImage(photo.url)}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* Extra photos revealed after "View All Photos" is clicked */}
                    {showAll && extraPhotos.length > 0 && (
                        <div className="photosGrid photosGridUniform extraPhotosGrid">
                            {extraPhotos.map((photo) => (
                                <div
                                    className="photoUniform"
                                    key={photo.id}
                                    style={{ backgroundImage: `url(${photo.url})` }}
                                    onClick={() => openImage(photo.url)}
                                />
                            ))}
                        </div>
                    )}

                    <p className="photoHint">Click any image to see a larger preview.</p>
                </>
            )}

            {hasMore && (
                <div className="viewAllWrap">
                    <button
                        type="button"
                        className="viewAllBtn"
                        onClick={() => setShowAll((prev) => !prev)}
                    >
                        {showAll ? (
                            <>
                                Show Less <FaChevronUp />
                            </>
                        ) : (
                            <>
                                View All Photos <FaArrowRight />
                            </>
                        )}
                    </button>
                </div>
            )}

            {zoomedImage && (
                <div className="photoLightbox" onClick={() => setZoomedImage(null)}>
                    <button
                        type="button"
                        className="lightboxClose"
                        onClick={() => setZoomedImage(null)}
                        aria-label="Close"
                    >
                        <FaTimes />
                    </button>
                    <img
                        src={zoomedImage}
                        alt="Zoomed race day"
                        className="lightboxImg"
                        onClick={(e) => e.stopPropagation()}
                    />
                </div>
            )}
        </section>
    );
}