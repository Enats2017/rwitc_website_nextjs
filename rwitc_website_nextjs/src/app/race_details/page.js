"use client";
import { Suspense } from "react";
import { useSearchParams } from "next/navigation";
import TopHeader from "../components/topheader/TopHeader";
import Footer from "../components/footer/Footer";
import Handicaps from "./components/Handicaps";
import Declarations from "./components/Declarations";
import Acceptance from "./components/Acceptance";
import RaceResult from "./components/RaceResult";
import RaceCard from "./components/Race_card";
import Photos from "./components/Photos";
import TrackWork from "./components/TrackWork";
import RatingChange from "./components/RatingChange"; 
import RaceDayReport from "./components/RaceDayReport";
import Sweepstake from "./components/Sweepstake";

function RaceDetailsContent() {

    const searchParams = useSearchParams();
    const type = searchParams.get("type");

    let ContentComponent = null;

    if (type === "handicaps") {
        ContentComponent = <Handicaps />;
    } else if (type === "declarations") {
        ContentComponent = <Declarations />;
    } else if (type === "acceptances") {
        ContentComponent = <Acceptance />;
    } else if (type === "raceResults") {
    ContentComponent = <RaceResult />;
    } else if (type === "raceCard") {
    ContentComponent = <RaceCard />;
    } else if (type === "ratingChange") {                
        ContentComponent = <RatingChange />;  
    } else if (type === "raceDayReport") {
        ContentComponent = <RaceDayReport />; 
    } else if (type === "sweepstakes") {
        ContentComponent = <Sweepstake />;
    } else if (type === "photos") {
        ContentComponent = <Photos />;
    } else if (type === "trackWork") {
        ContentComponent = <TrackWork />;
    } else {
        ContentComponent = <p>Invalid or missing type parameter.</p>;
    }

    return ContentComponent;
}

export default function RaceDetailsPage() {
    return (
        <>
            <TopHeader />
            <Suspense fallback={<div>Loading...</div>}>
                <RaceDetailsContent />
            </Suspense>
            <Footer />
        </>
    );
}