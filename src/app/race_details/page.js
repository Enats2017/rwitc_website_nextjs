"use client";

import { Suspense } from "react";
import { useSearchParams } from "next/navigation";
import TopHeader from "../components/topheader/TopHeader";
import Footer from "../components/footer/Footer";
import Handicaps from "./components/Handicaps";
import Declarations from "./components/Declarations";
import Acceptance from "./components/Acceptance";

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