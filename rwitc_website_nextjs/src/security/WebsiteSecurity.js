"use client";
import { useEffect } from "react";
export default function WebsiteSecurity() {
    useEffect(() => {
        const disableCopy = (e) => { e.preventDefault();};
        const disableDrag = (e) => { e.preventDefault();};
        const disableSelect = (e) => { if ( e.target.tagName !== "INPUT" && e.target.tagName !== "TEXTAREA" ) { e.preventDefault();}};
        document.addEventListener( "copy", disableCopy);
        document.addEventListener( "cut", disableCopy);
        document.addEventListener( "paste", disableCopy);
        document.addEventListener( "dragstart", disableDrag);
        document.addEventListener( "selectstart", disableSelect);
        return () => {
            document.removeEventListener( "copy", disableCopy);
            document.removeEventListener( "cut", disableCopy);
            document.removeEventListener( "paste", disableCopy);
            document.removeEventListener( "dragstart", disableDrag);
            document.removeEventListener( "selectstart", disableSelect);
        };
    }, []);
    return null;
}