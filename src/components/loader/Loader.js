"use client";

import Lottie from "lottie-react";

import horseAnimation from "../../../public/animations/Horse_Loader.json";

import "./Loader.css";

export default function Loader({ loading }) {

    if (!loading) return null;

    return (

        <div className="loaderOverlay">

            <div className="loaderBackdrop"></div>

            <div className="loaderAnimation">

                <Lottie
                    animationData={horseAnimation}
                    loop={true}
                    autoplay={true}
                />

            </div>

        </div>

    );

}