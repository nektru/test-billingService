--
-- PostgreSQL database dump
--

-- Dumped from database version 9.6.4
-- Dumped by pg_dump version 9.6.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

--
-- Name: holdstatus; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE holdstatus AS ENUM (
    'new',
    'accepted',
    'rejected'
);


ALTER TYPE holdstatus OWNER TO postgres;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: account; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE account (
    user_uuid uuid NOT NULL,
    currency character(3) NOT NULL,
    balance_amount integer DEFAULT 0 NOT NULL
);


ALTER TABLE account OWNER TO postgres;

--
-- Name: hold; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE hold (
    hold_uuid uuid NOT NULL,
    user_uuid uuid NOT NULL,
    currency character(3) NOT NULL,
    amount integer DEFAULT 0 NOT NULL,
    created timestamp without time zone DEFAULT now(),
    status holdstatus DEFAULT 'new'::holdstatus
);


ALTER TABLE hold OWNER TO postgres;

--
-- Name: account account_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY account
    ADD CONSTRAINT account_id PRIMARY KEY (user_uuid, currency);


--
-- Name: hold hold_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY hold
    ADD CONSTRAINT hold_pkey PRIMARY KEY (hold_uuid);


--
-- Name: hold hold_user_uuid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY hold
    ADD CONSTRAINT hold_user_uuid_fkey FOREIGN KEY (user_uuid, currency) REFERENCES account(user_uuid, currency) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

