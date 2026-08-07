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
import BeginnersGuide from "./horse_race/Beginners_Guide";
import JockeyStatistics from "./horse_race/JockeyStatistics";
import JockeyRidingWeight from "./horse_race/JockeyRidingWeight";
import TrainerStatistics from "./horse_race/TrainerStatistics";
import NoticeFromStewards from "./horse_race/NoticeFromStewards";
import StewardsReport from "./horse_race/StewardsReport";
import ReadyReckoner from "./horse_race/ReadyReckoner";
import BodyWeightHorse from "./horse_race/BodyWeightHorse";
import RecordTimings from "./horse_race/RecordTimings";
import SaddleClothNumbers from "./horse_race/SaddleClothNumbers";
import WageringOverview from "./wagering/WageringOverview";
import BeginnersluckRacing from "./wagering/BeginnersluckRacing";
import WageringTerms from "./wagering/WageringTerms";
import BettingPools from "./wagering/BettingPools";
import NationalTotePools from "./wagering/NationalTotePools";
import BettingChannels from "./wagering/BettingChannels";
import DeductionNorms from "./wagering/DeductionNorms";

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
        case "beginners-guide":
            return <BeginnersGuide />;
        case "jockeystatistics":
            return <JockeyStatistics />;
        case "jockeyridingweight":
            return <JockeyRidingWeight />;
        case "trainerstatistics":
            return <TrainerStatistics />;
        case "noticefromstewards":
            return <NoticeFromStewards />;
        case "steward-notice":
            return <StewardsReport />;
        case "readyreckoner":
            return <ReadyReckoner />;
        case "bodyweighthorse":
            return <BodyWeightHorse />;
        case "recordtimings":
            return <RecordTimings />;
        case "saddleclothnumbers":
            return <SaddleClothNumbers />;
        case "wageringoverview":
            return <WageringOverview />;
        case "beginnersluckracing":
            return <BeginnersluckRacing />;
        case "wageringterms":
            return <WageringTerms />;
        case "betting-pools":
            return <BettingPools />;
        case "national-tote-pools":
            return <NationalTotePools />;
        case "betting-channels":
            return <BettingChannels />;
        case "deduction-norms":
            return <DeductionNorms />;

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
