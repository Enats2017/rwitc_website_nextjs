import TopHeader from "../components/topheader/TopHeader";
import Footer from "../components/footer/Footer";
import Contact from "./Contact";

export const metadata = {
    title: "Contact Us | RWITC",
    description:
        "Contact the Royal Western India Turf Club for enquiries and racing information.",
};

export default function ContactPage() {
    return (
        <>
            <TopHeader />
            <Contact />
            <Footer />
        </>
    );
}