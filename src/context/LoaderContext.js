"use client";

import { createContext, useContext, useEffect, useState } from "react";

import Loader from "../components/loader/Loader";

const LoaderContext = createContext();

export function LoaderProvider({ children }) {

    // First time website open hone par loader show rahega
    const [loading, setLoading] = useState(true);

    useEffect(() => {

        const timer = setTimeout(() => {

            setLoading(false);

        }, 3000); // 2.5 seconds

        return () => clearTimeout(timer);

    }, []);

    const showLoader = () => {

        setLoading(true);

    };

    const hideLoader = () => {

        setLoading(false);

    };

    return (

        <LoaderContext.Provider
            value={{
                loading,
                showLoader,
                hideLoader,
            }}
        >

            <Loader loading={loading} />

            {children}

        </LoaderContext.Provider>

    );

}

export function useLoader() {

    return useContext(LoaderContext);

}