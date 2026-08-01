import TopHeader from "./components/topheader/TopHeader";
import Hero from "./components/hero/Hero";
import MediaSection from "./components/mediasection/MediaSection";
import NewsRoom from "./components/newsroom/NewsRoom";
import FeatureSection from "./components/featureSection/FeatureSection";
import Sponsors from "./components/Sponsors/Sponsors";
import Footer from "./components/footer/Footer";
export default function Home() {
  return (
    <>
      <TopHeader />
      <Hero/>
      <MediaSection/>
      <NewsRoom/>
      <FeatureSection/>
      <Sponsors/>
      <Footer/>
    </>
  );
}