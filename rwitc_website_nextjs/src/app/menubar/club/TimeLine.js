"use client";

import { FaHorseHead } from "react-icons/fa";
import "./TimeLine.css";

export default function TimeLine() {

    const events = [
        {
            year: "1798",
            text: "A two-day race meeting held in Bombay after raising funds via an appeal in local newspapers.",
        },
        {
            year: "1800",
            text: "Sir Charles Forbes, G Hall, A Campbell and P Haddow start Bombay Turf Club in Byculla Club Grounds acquired through the good offices of Dorabji Rustomji, the Patel of Bombay.",
        },
        {
            year: "1819",
            text: "The first horse race held in Pune with a 100-guinea cup donated by the British Resident – and later Governor of Bombay – the Hon. Mr Mountstuart Elphinstone, as the trophy – one year after the end of the Last Anglo-Maratha War and the Peshwa reign.",
        },
        {
            year: "1830",
            text: "The Pune Race Course built on the present site in the Cantonment near the Empress Garden.",
        },
        {
            year: "1864",
            text: "Bombay Turf Club changes its name to Western India Turf Club. A move to a new venue is also mooted.",
        },
        {
            year: "1870",
            text: "The Byculla Club Purse starts; out of it, are given prizes for the first time. Perhaps, this is the origin of the Byculla Club Cup.",
        },
        {
            year: "1878",
            text: "Racing shifts to Mahalaxmi Flats, built on the marshy land donated by the Philanthropist and Industrialist Sir Cusrow N Wadia – under the direction and supervision of Major JE Hughes – but is abandoned after one season because of difficulty of access and foul odour from the open drain nearby.",
        },
        {
            year: "1883",
            text: "Racing finally shifts to Mahalaxmi.",
        },
        {
            year: "1886 - 1897",
            text: "Agreement reached with the Calcutta Turf Club \"that no course in India be allowed to race under Rules without being controlled by either of the two Turf Authorities.\"",
        },
        {
            year: "1923",
            text: "The Eclipse Stakes of India run for the first time.",
        },
        {
            year: "1935",
            text: "HRH King George V, Emperor of India, grants permission to add the prefix \"Royal\" to the Club's name.",
        },
        {
            year: "1936",
            text: "Electric clock installed.",
        },
        {
            year: "1938",
            text: "The Apprentice Jockeys' School started under the guidance of the Stipendiary Stewards of the Club.",
        },
        {
            year: "1942 - 43",
            text: "The Indian classics, restricted to horses bred in India, introduced. Princess Beautiful, a Maharaja of Baroda filly, wins the Indian 1000 Guineas, the Indian 2000 Guineas and the Indian Derby.",
        },
        {
            year: "1949",
            text: "Kheem Singh is the first Indian jockey to win the Indian Derby, thus far dominated by foreign riders.",
        },
        {
            year: "1961",
            text: "HRH Queen Elizabeth II of Great Britain visits Mahalaxmi.",
        },
        {
            year: "1963",
            text: "The inaugural Indian Invitation Cup held at Mahalaxmi.",
        },
        {
            year: "1965",
            text: "Rose Royale wins the Indian Fillies Triple Crown: 1000 Guineas, Indian Oaks, and Indian Derby.",
        },
        {
            year: "1967",
            text: "Joint Mumbai-Pune Tote and inter-venue betting starts. Also, voice relay of announcements from the live centre to the ghost centre begins.",
        },
        {
            year: "1971",
            text: "An all-time record jackpot of Rs.48,00,000/- won at Mahalaxmi by the Hindi film lyricist, Rajendra Krishnan.",
        },
        {
            year: "1974",
            text: "Interstate intervenue betting between Mumbai/Pune and Bangalore begins.",
        },
        {
            year: "1985",
            text: "The first running of the McDowell Indian Derby.",
        },
        {
            year: "1987",
            text: "RWITC enters into an agreement with the Amateur Riders' Club to run the Apprentice Jockeys' School.",
        },
        {
            year: "1990",
            text: "India's first Million for 3-year olds is the Poonawalla Breeders' Million. Le Gris Cheval wins the inaugural edition. It later reaches the Grade III status (1994), then Grade II (1995) and finally Grade I (1996).",
        },
        {
            year: "2002",
            text: "Inter-venue betting with Hyderabad starts.",
        },
        {
            year: "2002",
            text: "Ten Sports starts live telecast of racing action at Mahalaxmi.",
        },
        {
            year: "2002",
            text: "The Western India Race Horse Owners' Association (WIRHOA) starts the WIRHOA Racing Awards for owners and horses.",
        },
        {
            year: "2002",
            text: "State-of-the-art photo finish camera installed.",
        },
        {
            year: "2004",
            text: "Innovative Monsoon track preparation (laying the tan outside it from 1400 metres to the winning post) to prevent horses running on the far out during heavy rains do not hit hard on the ground.",
        },
        {
            year: "2006",
            text: "For the first time ever, 7 multinationals sponsor the entire December race day card.",
        },
        {
            year: "2008",
            text: "Equine influenza epidemic in Pune stables. Mumbai 2008 - 2009 season postponed.",
        },
        {
            year: "2008",
            text: "Fire destroys third deck of the Members Stand in July 2008. (Restored in September 2009).",
        },
        {
            year: "2009",
            text: "The new Club House with Gym & Card Room for members inaugrated on Dec 6th 2009.",
        },
    ];

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Timeline</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="timelineWrap">

                        {events.map((event, index) => (
                            <div className="timelineItem" key={index}>

                                <div className="timelineMarker">
                                    <span className="timelineDot"></span>
                                </div>

                                <div className="timelineContent">
                                    <span className="timelineYear">{event.year}</span>
                                    <p className="timelineText">{event.text}</p>
                                </div>

                            </div>
                        ))}

                    </div>

                </div>

            </div>

        </section>

    );

}