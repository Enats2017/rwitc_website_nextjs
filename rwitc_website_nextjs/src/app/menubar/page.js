"use client";
import { Suspense } from "react";
import { useSearchParams } from "next/navigation";
import TopHeader from "../components/topheader/TopHeader";
import Footer from "../components/footer/Footer";
import About from "./club/About";
import VissionMission from "./club/Vission_Mission";
import Structure from "./club/Structure";
import ManagingCommittee from "./club/Managing_Committee";
import StewardsClub from "./club/StewardsClub";
import BoardAppeal from "./club/BoardAppeal";
import TimeLine from "./club/TimeLine";
import BequeathingColonialLegacy from "./club/BequeathingColonialLegacy";
import CharityRaceDays from "./club/CharityRaceDays";
import ContributingCommunity from "./club/ContributingCommunity";
import ResponsibleGambling from "./club/ResponsibleGambling";
import CareersClub from "./club/CareersClub";

function MenuBarContent() {
    const searchParams = useSearchParams();
    const type = searchParams.get("type");
    switch (type) {
        case "about":
            return <About />;
        case "vision-mission":
            return <VissionMission />;
        case "structure":
            return <Structure />;
        case "managing-committee":
            return <ManagingCommittee />;
        case "stewardsclub":
            return <StewardsClub />;
        case "boardappeal":
            return <BoardAppeal />;
        case "timeline":
            return <TimeLine />;
        case "bequeathingcoloniallegacy":
            return <BequeathingColonialLegacy />
        case "charityracedays":
            return <CharityRaceDays />
        case "contributingcommunity":
            return <ContributingCommunity />
        case "responsible-gambling":
            return <ResponsibleGambling />;
        case "careersclub":
            return <CareersClub />;

        default:
            return (
                <h2
                    style={{
                        minHeight: "60vh",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        fontSize: "24px",
                        fontWeight: "600",
                    }}
                >
                    Page Not Found
                </h2>
            );
    }
}
export default function MenuBarPage() {
    return (
        <>
            <TopHeader />
            <Suspense fallback={<div>Loading...</div>}>
                <MenuBarContent />
            </Suspense>
            <Footer />
        </>
    );
}
