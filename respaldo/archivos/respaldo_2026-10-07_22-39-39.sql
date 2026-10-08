--
-- PostgreSQL database dump
--

\restrict 9T5maxz3iQ2k0nksZ57a88RJ2HkzThbGofgcozC8GOVZq7KaNIlZdQCrT5haQEB

-- Dumped from database version 17.6
-- Dumped by pg_dump version 17.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: auth; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA auth;


ALTER SCHEMA auth OWNER TO supabase_admin;

--
-- Name: extensions; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA extensions;


ALTER SCHEMA extensions OWNER TO postgres;

--
-- Name: graphql; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA graphql;


ALTER SCHEMA graphql OWNER TO supabase_admin;

--
-- Name: graphql_public; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA graphql_public;


ALTER SCHEMA graphql_public OWNER TO supabase_admin;

--
-- Name: pgbouncer; Type: SCHEMA; Schema: -; Owner: pgbouncer
--

CREATE SCHEMA pgbouncer;


ALTER SCHEMA pgbouncer OWNER TO pgbouncer;

--
-- Name: realtime; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA realtime;


ALTER SCHEMA realtime OWNER TO supabase_admin;

--
-- Name: storage; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA storage;


ALTER SCHEMA storage OWNER TO supabase_admin;

--
-- Name: vault; Type: SCHEMA; Schema: -; Owner: supabase_admin
--

CREATE SCHEMA vault;


ALTER SCHEMA vault OWNER TO supabase_admin;

--
-- Name: pg_stat_statements; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_stat_statements WITH SCHEMA extensions;


--
-- Name: EXTENSION pg_stat_statements; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pg_stat_statements IS 'track planning and execution statistics of all SQL statements executed';


--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA extensions;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: supabase_vault; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS supabase_vault WITH SCHEMA vault;


--
-- Name: EXTENSION supabase_vault; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION supabase_vault IS 'Supabase Vault Extension';


--
-- Name: uuid-ossp; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA extensions;


--
-- Name: EXTENSION "uuid-ossp"; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION "uuid-ossp" IS 'generate universally unique identifiers (UUIDs)';


--
-- Name: aal_level; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.aal_level AS ENUM (
    'aal1',
    'aal2',
    'aal3'
);


ALTER TYPE auth.aal_level OWNER TO supabase_auth_admin;

--
-- Name: code_challenge_method; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.code_challenge_method AS ENUM (
    's256',
    'plain'
);


ALTER TYPE auth.code_challenge_method OWNER TO supabase_auth_admin;

--
-- Name: factor_status; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.factor_status AS ENUM (
    'unverified',
    'verified'
);


ALTER TYPE auth.factor_status OWNER TO supabase_auth_admin;

--
-- Name: factor_type; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.factor_type AS ENUM (
    'totp',
    'webauthn',
    'phone',
    'recovery_code'
);


ALTER TYPE auth.factor_type OWNER TO supabase_auth_admin;

--
-- Name: oauth_authorization_status; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.oauth_authorization_status AS ENUM (
    'pending',
    'approved',
    'denied',
    'expired'
);


ALTER TYPE auth.oauth_authorization_status OWNER TO supabase_auth_admin;

--
-- Name: oauth_client_type; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.oauth_client_type AS ENUM (
    'public',
    'confidential'
);


ALTER TYPE auth.oauth_client_type OWNER TO supabase_auth_admin;

--
-- Name: oauth_registration_type; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.oauth_registration_type AS ENUM (
    'dynamic',
    'manual'
);


ALTER TYPE auth.oauth_registration_type OWNER TO supabase_auth_admin;

--
-- Name: oauth_response_type; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.oauth_response_type AS ENUM (
    'code'
);


ALTER TYPE auth.oauth_response_type OWNER TO supabase_auth_admin;

--
-- Name: one_time_token_type; Type: TYPE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TYPE auth.one_time_token_type AS ENUM (
    'confirmation_token',
    'reauthentication_token',
    'recovery_token',
    'email_change_token_new',
    'email_change_token_current',
    'phone_change_token'
);


ALTER TYPE auth.one_time_token_type OWNER TO supabase_auth_admin;

--
-- Name: action; Type: TYPE; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE TYPE realtime.action AS ENUM (
    'INSERT',
    'UPDATE',
    'DELETE',
    'TRUNCATE',
    'ERROR'
);


ALTER TYPE realtime.action OWNER TO supabase_realtime_admin;

--
-- Name: equality_op; Type: TYPE; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE TYPE realtime.equality_op AS ENUM (
    'eq',
    'neq',
    'lt',
    'lte',
    'gt',
    'gte',
    'in',
    'like',
    'ilike',
    'is',
    'match',
    'imatch',
    'isdistinct'
);


ALTER TYPE realtime.equality_op OWNER TO supabase_realtime_admin;

--
-- Name: user_defined_filter; Type: TYPE; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE TYPE realtime.user_defined_filter AS (
	column_name text,
	op realtime.equality_op,
	value text,
	negate boolean
);


ALTER TYPE realtime.user_defined_filter OWNER TO supabase_realtime_admin;

--
-- Name: wal_column; Type: TYPE; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE TYPE realtime.wal_column AS (
	name text,
	type_name text,
	type_oid oid,
	value jsonb,
	is_pkey boolean,
	is_selectable boolean
);


ALTER TYPE realtime.wal_column OWNER TO supabase_realtime_admin;

--
-- Name: wal_rls; Type: TYPE; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE TYPE realtime.wal_rls AS (
	wal jsonb,
	is_rls_enabled boolean,
	subscription_ids uuid[],
	errors text[]
);


ALTER TYPE realtime.wal_rls OWNER TO supabase_realtime_admin;

--
-- Name: buckettype; Type: TYPE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TYPE storage.buckettype AS ENUM (
    'STANDARD',
    'ANALYTICS',
    'VECTOR'
);


ALTER TYPE storage.buckettype OWNER TO supabase_storage_admin;

--
-- Name: email(); Type: FUNCTION; Schema: auth; Owner: supabase_auth_admin
--

CREATE FUNCTION auth.email() RETURNS text
    LANGUAGE sql STABLE
    AS $$
  select 
  coalesce(
    nullif(current_setting('request.jwt.claim.email', true), ''),
    (nullif(current_setting('request.jwt.claims', true), '')::jsonb ->> 'email')
  )::text
$$;


ALTER FUNCTION auth.email() OWNER TO supabase_auth_admin;

--
-- Name: FUNCTION email(); Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON FUNCTION auth.email() IS 'Deprecated. Use auth.jwt() -> ''email'' instead.';


--
-- Name: jwt(); Type: FUNCTION; Schema: auth; Owner: supabase_auth_admin
--

CREATE FUNCTION auth.jwt() RETURNS jsonb
    LANGUAGE sql STABLE
    AS $$
  select 
    coalesce(
        nullif(current_setting('request.jwt.claim', true), ''),
        nullif(current_setting('request.jwt.claims', true), '')
    )::jsonb
$$;


ALTER FUNCTION auth.jwt() OWNER TO supabase_auth_admin;

--
-- Name: role(); Type: FUNCTION; Schema: auth; Owner: supabase_auth_admin
--

CREATE FUNCTION auth.role() RETURNS text
    LANGUAGE sql STABLE
    AS $$
  select 
  coalesce(
    nullif(current_setting('request.jwt.claim.role', true), ''),
    (nullif(current_setting('request.jwt.claims', true), '')::jsonb ->> 'role')
  )::text
$$;


ALTER FUNCTION auth.role() OWNER TO supabase_auth_admin;

--
-- Name: FUNCTION role(); Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON FUNCTION auth.role() IS 'Deprecated. Use auth.jwt() -> ''role'' instead.';


--
-- Name: uid(); Type: FUNCTION; Schema: auth; Owner: supabase_auth_admin
--

CREATE FUNCTION auth.uid() RETURNS uuid
    LANGUAGE sql STABLE
    AS $$
  select 
  coalesce(
    nullif(current_setting('request.jwt.claim.sub', true), ''),
    (nullif(current_setting('request.jwt.claims', true), '')::jsonb ->> 'sub')
  )::uuid
$$;


ALTER FUNCTION auth.uid() OWNER TO supabase_auth_admin;

--
-- Name: FUNCTION uid(); Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON FUNCTION auth.uid() IS 'Deprecated. Use auth.jwt() -> ''sub'' instead.';


--
-- Name: grant_pg_cron_access(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.grant_pg_cron_access() RETURNS event_trigger
    LANGUAGE plpgsql
    SET search_path TO ''
    AS $$
BEGIN
  IF EXISTS (
    SELECT
    FROM pg_event_trigger_ddl_commands() AS ev
    JOIN pg_extension AS ext
    ON ev.objid = ext.oid
    WHERE ext.extname = 'pg_cron'
  )
  THEN
    grant usage on schema cron to postgres with grant option;

    alter default privileges in schema cron grant all on tables to postgres with grant option;
    alter default privileges in schema cron grant all on functions to postgres with grant option;
    alter default privileges in schema cron grant all on sequences to postgres with grant option;

    alter default privileges for user supabase_admin in schema cron grant all
        on sequences to postgres with grant option;
    alter default privileges for user supabase_admin in schema cron grant all
        on tables to postgres with grant option;
    alter default privileges for user supabase_admin in schema cron grant all
        on functions to postgres with grant option;

    grant all privileges on all tables in schema cron to postgres with grant option;
    revoke all on table cron.job from postgres;
    grant select on table cron.job to postgres with grant option;
    revoke trigger on cron.job_run_details from postgres;
  END IF;
END;
$$;


ALTER FUNCTION extensions.grant_pg_cron_access() OWNER TO supabase_admin;

--
-- Name: FUNCTION grant_pg_cron_access(); Type: COMMENT; Schema: extensions; Owner: supabase_admin
--

COMMENT ON FUNCTION extensions.grant_pg_cron_access() IS 'Grants access to pg_cron';


--
-- Name: grant_pg_graphql_access(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.grant_pg_graphql_access() RETURNS event_trigger
    LANGUAGE plpgsql
    SET search_path TO ''
    AS $_$
begin
    if not exists (
        select 1
        from pg_catalog.pg_event_trigger_ddl_commands() ev
        join pg_catalog.pg_extension e on ev.objid = e.oid
        where e.extname = 'pg_graphql'
    ) then
        return;
    end if;

    drop function if exists graphql_public.graphql;
    create or replace function graphql_public.graphql(
        "operationName" text default null,
        query text default null,
        variables jsonb default null,
        extensions jsonb default null
    )
        returns jsonb
        language sql
    as $$
        select graphql.resolve(
            query := query,
            variables := coalesce(variables, '{}'),
            "operationName" := "operationName",
            extensions := extensions
        );
    $$;

    -- Attach the wrapper to the extension so DROP EXTENSION cascades to it,
    -- which in turn triggers set_graphql_placeholder to reinstall the "not enabled" stub.
    alter extension pg_graphql add function graphql_public.graphql(text, text, jsonb, jsonb);

    grant usage on schema graphql to postgres, anon, authenticated, service_role;
    grant execute on function graphql.resolve to postgres, anon, authenticated, service_role;
    grant usage on schema graphql to postgres with grant option;
    grant usage on schema graphql_public to postgres with grant option;
end;
$_$;


ALTER FUNCTION extensions.grant_pg_graphql_access() OWNER TO supabase_admin;

--
-- Name: FUNCTION grant_pg_graphql_access(); Type: COMMENT; Schema: extensions; Owner: supabase_admin
--

COMMENT ON FUNCTION extensions.grant_pg_graphql_access() IS 'Grants access to pg_graphql';


--
-- Name: grant_pg_net_access(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.grant_pg_net_access() RETURNS event_trigger
    LANGUAGE plpgsql
    SET search_path TO ''
    AS $$
BEGIN
  IF EXISTS (
    SELECT 1
    FROM pg_event_trigger_ddl_commands() AS ev
    JOIN pg_extension AS ext
    ON ev.objid = ext.oid
    WHERE ext.extname = 'pg_net'
  )
  THEN
    IF NOT EXISTS (
      SELECT 1
      FROM pg_roles
      WHERE rolname = 'supabase_functions_admin'
    )
    THEN
      CREATE USER supabase_functions_admin NOINHERIT CREATEROLE LOGIN NOREPLICATION;
    END IF;

    GRANT USAGE ON SCHEMA net TO supabase_functions_admin, postgres, anon, authenticated, service_role;

    IF EXISTS (
      SELECT FROM pg_extension
      WHERE extname = 'pg_net'
      -- all versions in use on existing projects as of 2025-02-20
      -- version 0.12.0 onwards don't need these applied
      AND extversion IN ('0.2', '0.6', '0.7', '0.7.1', '0.8.0', '0.10.0', '0.11.0')
    ) THEN
      ALTER function net.http_get(url text, params jsonb, headers jsonb, timeout_milliseconds integer) SECURITY DEFINER;
      ALTER function net.http_post(url text, body jsonb, params jsonb, headers jsonb, timeout_milliseconds integer) SECURITY DEFINER;

      ALTER function net.http_get(url text, params jsonb, headers jsonb, timeout_milliseconds integer) SET search_path = net;
      ALTER function net.http_post(url text, body jsonb, params jsonb, headers jsonb, timeout_milliseconds integer) SET search_path = net;

      REVOKE ALL ON FUNCTION net.http_get(url text, params jsonb, headers jsonb, timeout_milliseconds integer) FROM PUBLIC;
      REVOKE ALL ON FUNCTION net.http_post(url text, body jsonb, params jsonb, headers jsonb, timeout_milliseconds integer) FROM PUBLIC;

      GRANT EXECUTE ON FUNCTION net.http_get(url text, params jsonb, headers jsonb, timeout_milliseconds integer) TO supabase_functions_admin, postgres, anon, authenticated, service_role;
      GRANT EXECUTE ON FUNCTION net.http_post(url text, body jsonb, params jsonb, headers jsonb, timeout_milliseconds integer) TO supabase_functions_admin, postgres, anon, authenticated, service_role;
    END IF;
  END IF;
END;
$$;


ALTER FUNCTION extensions.grant_pg_net_access() OWNER TO supabase_admin;

--
-- Name: FUNCTION grant_pg_net_access(); Type: COMMENT; Schema: extensions; Owner: supabase_admin
--

COMMENT ON FUNCTION extensions.grant_pg_net_access() IS 'Grants access to pg_net';


--
-- Name: pgrst_ddl_watch(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.pgrst_ddl_watch() RETURNS event_trigger
    LANGUAGE plpgsql
    SET search_path TO ''
    AS $$
DECLARE
  cmd record;
BEGIN
  FOR cmd IN SELECT * FROM pg_event_trigger_ddl_commands()
  LOOP
    IF cmd.command_tag IN (
      'CREATE SCHEMA', 'ALTER SCHEMA'
    , 'CREATE TABLE', 'CREATE TABLE AS', 'SELECT INTO', 'ALTER TABLE'
    , 'CREATE FOREIGN TABLE', 'ALTER FOREIGN TABLE'
    , 'CREATE VIEW', 'ALTER VIEW'
    , 'CREATE MATERIALIZED VIEW', 'ALTER MATERIALIZED VIEW'
    , 'CREATE FUNCTION', 'ALTER FUNCTION'
    , 'CREATE TRIGGER'
    , 'CREATE TYPE', 'ALTER TYPE'
    , 'CREATE RULE'
    , 'COMMENT'
    )
    -- don't notify in case of CREATE TEMP table or other objects created on pg_temp
    AND cmd.schema_name is distinct from 'pg_temp'
    THEN
      NOTIFY pgrst, 'reload schema';
    END IF;
  END LOOP;
END; $$;


ALTER FUNCTION extensions.pgrst_ddl_watch() OWNER TO supabase_admin;

--
-- Name: pgrst_drop_watch(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.pgrst_drop_watch() RETURNS event_trigger
    LANGUAGE plpgsql
    SET search_path TO ''
    AS $$
DECLARE
  obj record;
BEGIN
  FOR obj IN SELECT * FROM pg_event_trigger_dropped_objects()
  LOOP
    IF obj.object_type IN (
      'schema'
    , 'table'
    , 'foreign table'
    , 'view'
    , 'materialized view'
    , 'function'
    , 'trigger'
    , 'type'
    , 'rule'
    )
    AND obj.is_temporary IS false -- no pg_temp objects
    THEN
      NOTIFY pgrst, 'reload schema';
    END IF;
  END LOOP;
END; $$;


ALTER FUNCTION extensions.pgrst_drop_watch() OWNER TO supabase_admin;

--
-- Name: set_graphql_placeholder(); Type: FUNCTION; Schema: extensions; Owner: supabase_admin
--

CREATE FUNCTION extensions.set_graphql_placeholder() RETURNS event_trigger
    LANGUAGE plpgsql
    SET search_path TO ''
    AS $_$
    DECLARE
    graphql_is_dropped bool;
    BEGIN
    graphql_is_dropped = (
        SELECT ev.schema_name = 'graphql_public'
        FROM pg_event_trigger_dropped_objects() AS ev
        WHERE ev.schema_name = 'graphql_public'
    );

    IF graphql_is_dropped
    THEN
        create or replace function graphql_public.graphql(
            "operationName" text default null,
            query text default null,
            variables jsonb default null,
            extensions jsonb default null
        )
            returns jsonb
            language plpgsql
            set search_path to ''
        as $$
            DECLARE
                server_version float;
            BEGIN
                server_version = (SELECT (SPLIT_PART((select version()), ' ', 2))::float);

                IF server_version >= 14 THEN
                    RETURN jsonb_build_object(
                        'errors', jsonb_build_array(
                            jsonb_build_object(
                                'message', 'pg_graphql extension is not enabled.'
                            )
                        )
                    );
                ELSE
                    RETURN jsonb_build_object(
                        'errors', jsonb_build_array(
                            jsonb_build_object(
                                'message', 'pg_graphql is only available on projects running Postgres 14 onwards.'
                            )
                        )
                    );
                END IF;
            END;
        $$;
    END IF;

    END;
$_$;


ALTER FUNCTION extensions.set_graphql_placeholder() OWNER TO supabase_admin;

--
-- Name: FUNCTION set_graphql_placeholder(); Type: COMMENT; Schema: extensions; Owner: supabase_admin
--

COMMENT ON FUNCTION extensions.set_graphql_placeholder() IS 'Reintroduces placeholder function for graphql_public.graphql';


--
-- Name: graphql(text, text, jsonb, jsonb); Type: FUNCTION; Schema: graphql_public; Owner: supabase_admin
--

CREATE FUNCTION graphql_public.graphql("operationName" text DEFAULT NULL::text, query text DEFAULT NULL::text, variables jsonb DEFAULT NULL::jsonb, extensions jsonb DEFAULT NULL::jsonb) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$
            DECLARE
                server_version float;
            BEGIN
                server_version = (SELECT (SPLIT_PART((select version()), ' ', 2))::float);

                IF server_version >= 14 THEN
                    RETURN jsonb_build_object(
                        'errors', jsonb_build_array(
                            jsonb_build_object(
                                'message', 'pg_graphql extension is not enabled.'
                            )
                        )
                    );
                ELSE
                    RETURN jsonb_build_object(
                        'errors', jsonb_build_array(
                            jsonb_build_object(
                                'message', 'pg_graphql is only available on projects running Postgres 14 onwards.'
                            )
                        )
                    );
                END IF;
            END;
        $$;


ALTER FUNCTION graphql_public.graphql("operationName" text, query text, variables jsonb, extensions jsonb) OWNER TO supabase_admin;

--
-- Name: get_auth(text); Type: FUNCTION; Schema: pgbouncer; Owner: supabase_admin
--

CREATE FUNCTION pgbouncer.get_auth(p_usename text) RETURNS TABLE(username text, password text)
    LANGUAGE plpgsql SECURITY DEFINER
    SET search_path TO ''
    AS $_$
  BEGIN
      RAISE DEBUG 'PgBouncer auth request: %', p_usename;

      RETURN QUERY
      SELECT
          rolname::text,
          CASE WHEN rolvaliduntil < now()
              THEN null
              ELSE rolpassword::text
          END
      FROM pg_authid
      WHERE rolname=$1 and rolcanlogin;
  END;
  $_$;


ALTER FUNCTION pgbouncer.get_auth(p_usename text) OWNER TO supabase_admin;

--
-- Name: fn_calcular_precio_tienda(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_calcular_precio_tienda() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE

    v_impuesto NUMERIC(5,2);

    v_margen NUMERIC(5,2);

    v_precio_calculado NUMERIC(12,2);

BEGIN

    -- Obtener impuesto configurado para el producto

    SELECT i.porcentaje
    INTO v_impuesto
    FROM public.producto p

    INNER JOIN public.impuesto i
        ON i.id_impuesto = p.id_impuesto

    WHERE p.id_producto = NEW.id_producto;


    IF v_impuesto IS NULL THEN
        RAISE EXCEPTION
        'No se encontró el impuesto del producto %',
        NEW.id_producto;
    END IF;


    -- Obtener margen seleccionado

    SELECT porcentaje
    INTO v_margen

    FROM public.margen_ganancia

    WHERE id_margen = NEW.id_margen;


    IF v_margen IS NULL THEN
        RAISE EXCEPTION
        'No se encontró el margen %',
        NEW.id_margen;
    END IF;


    -- =====================================================
    -- COSTO CON IMPUESTO
    --
    -- Ejemplo:
    -- costo = 200
    -- IVA = 13%
    --
    -- 200 * 1.13 = 226
    -- =====================================================

    NEW.precio_con_impuesto :=
        ROUND(
            NEW.costo *
            (1 + (v_impuesto / 100)),
            2
        );


    -- =====================================================
    -- APLICAR MARGEN
    --
    -- Nuestra tabla guarda:
    --
    -- 40 = factor 1.4
    -- 50 = factor 1.5
    -- 60 = factor 1.6
    -- 70 = factor 1.7
    --
    -- Ejemplo:
    -- 226 * 1.4 = 316.40
    -- =====================================================

    v_precio_calculado :=
        NEW.precio_con_impuesto *
        (1 + (v_margen / 100));


    -- =====================================================
    -- REDONDEAR SIEMPRE HACIA ARRIBA
    -- AL SIGUIENTE MÚLTIPLO DE ₡25
    --
    -- 316.40 -> 325
    -- 340    -> 350
    -- 425    -> 425
    -- 436    -> 450
    -- =====================================================

    NEW.precio_venta :=
        CEIL(v_precio_calculado / 25.0) * 25;


    RETURN NEW;

END;
$$;


ALTER FUNCTION public.fn_calcular_precio_tienda() OWNER TO postgres;

--
-- Name: fn_cerrar_precio_anterior(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_cerrar_precio_anterior() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN

    IF NEW.estado = TRUE
       AND NEW.fecha_fin IS NULL
    THEN

        UPDATE public.precio_tienda

        SET
            estado = FALSE,
            fecha_fin = COALESCE(
                NEW.fecha_inicio,
                NOW()
            )

        WHERE id_producto = NEW.id_producto

          AND id_tienda = NEW.id_tienda

          AND estado = TRUE

          AND fecha_fin IS NULL;

    END IF;


    RETURN NEW;

END;
$$;


ALTER FUNCTION public.fn_cerrar_precio_anterior() OWNER TO postgres;

--
-- Name: rls_auto_enable(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.rls_auto_enable() RETURNS event_trigger
    LANGUAGE plpgsql SECURITY DEFINER
    SET search_path TO 'pg_catalog'
    AS $$
DECLARE
  cmd record;
BEGIN
  FOR cmd IN
    SELECT *
    FROM pg_event_trigger_ddl_commands()
    WHERE command_tag IN ('CREATE TABLE', 'CREATE TABLE AS', 'SELECT INTO')
      AND object_type IN ('table','partitioned table')
  LOOP
     IF cmd.schema_name IS NOT NULL AND cmd.schema_name IN ('public') AND cmd.schema_name NOT IN ('pg_catalog','information_schema') AND cmd.schema_name NOT LIKE 'pg_toast%' AND cmd.schema_name NOT LIKE 'pg_temp%' THEN
      BEGIN
        EXECUTE format('alter table if exists %s enable row level security', cmd.object_identity);
        RAISE LOG 'rls_auto_enable: enabled RLS on %', cmd.object_identity;
      EXCEPTION
        WHEN OTHERS THEN
          RAISE LOG 'rls_auto_enable: failed to enable RLS on %', cmd.object_identity;
      END;
     ELSE
        RAISE LOG 'rls_auto_enable: skip % (either system schema or not in enforced list: %.)', cmd.object_identity, cmd.schema_name;
     END IF;
  END LOOP;
END;
$$;


ALTER FUNCTION public.rls_auto_enable() OWNER TO postgres;

--
-- Name: simular_precio(bigint, bigint, numeric); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.simular_precio(p_id_producto bigint, p_id_margen bigint, p_costo numeric) RETURNS TABLE(porcentaje_impuesto numeric, porcentaje_margen numeric, factor_margen numeric, costo_sin_impuesto numeric, costo_con_impuesto numeric, precio_calculado numeric, precio_final numeric)
    LANGUAGE plpgsql STABLE
    AS $$
DECLARE

    v_impuesto NUMERIC;

    v_margen NUMERIC;

    v_con_impuesto NUMERIC;

    v_calculado NUMERIC;

    v_final NUMERIC;

BEGIN

    -- Obtener impuesto

    SELECT i.porcentaje

    INTO v_impuesto

    FROM public.producto p

    INNER JOIN public.impuesto i
        ON i.id_impuesto = p.id_impuesto

    WHERE p.id_producto = p_id_producto;


    -- Obtener margen

    SELECT porcentaje

    INTO v_margen

    FROM public.margen_ganancia

    WHERE id_margen = p_id_margen;


    -- Costo con impuesto

    v_con_impuesto :=
        ROUND(
            p_costo *
            (1 + (v_impuesto / 100)),
            2
        );


    -- Precio aplicando margen

    v_calculado :=
        ROUND(
            v_con_impuesto *
            (1 + (v_margen / 100)),
            2
        );


    -- Precio comercial final

    v_final :=
        CEIL(v_calculado / 25.0) * 25;


    RETURN QUERY

    SELECT

        v_impuesto,

        v_margen,

        (1 + (v_margen / 100)),

        p_costo,

        v_con_impuesto,

        v_calculado,

        v_final;

END;
$$;


ALTER FUNCTION public.simular_precio(p_id_producto bigint, p_id_margen bigint, p_costo numeric) OWNER TO postgres;

--
-- Name: apply_rls(jsonb, integer); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer DEFAULT (1024 * 1024)) RETURNS SETOF realtime.wal_rls
    LANGUAGE plpgsql
    AS $$
declare
    -- Regclass of the table e.g. public.notes
    entity_ regclass = (quote_ident(wal ->> 'schema') || '.' || quote_ident(wal ->> 'table'))::regclass;

    -- I, U, D, T: insert, update ...
    action realtime.action = (
        case wal ->> 'action'
            when 'I' then 'INSERT'
            when 'U' then 'UPDATE'
            when 'D' then 'DELETE'
            else 'ERROR'
        end
    );

    -- Is row level security enabled for the table
    is_rls_enabled bool = relrowsecurity from pg_class where oid = entity_;

    subscriptions realtime.subscription[] = array_agg(subs)
        from
            realtime.subscription subs
        where
            subs.entity = entity_
            -- Filter by action early - only get subscriptions interested in this action
            -- action_filter column can be: '*' (all), 'INSERT', 'UPDATE', or 'DELETE'
            and (subs.action_filter = '*' or subs.action_filter = action::text);

    -- Subscription vars
    working_role regrole;
    working_selected_columns text[];
    claimed_role regrole;
    claims jsonb;

    subscription_id uuid;
    subscription_has_access bool;
    visible_to_subscription_ids uuid[] = '{}';

    -- structured info for wal's columns
    columns realtime.wal_column[];
    -- previous identity values for update/delete
    old_columns realtime.wal_column[];

    error_record_exceeds_max_size boolean = octet_length(wal::text) > max_record_bytes;

    -- Primary jsonb output for record
    output jsonb;

    -- Loop record for iterating unique roles (outer loop)
    role_record record;
    -- Loop record for iterating unique selected_columns within a role (inner loop)
    cols_record record;
    -- Subscription ids visible at the role level (before fanning out by selected_columns)
    visible_role_sub_ids uuid[] = '{}';

begin
    perform set_config('role', null, true);

    columns =
        array_agg(
            (
                x->>'name',
                x->>'type',
                x->>'typeoid',
                realtime.cast(
                    (x->'value') #>> '{}',
                    coalesce(
                        (x->>'typeoid')::regtype, -- null when wal2json version <= 2.4
                        (x->>'type')::regtype
                    )
                ),
                (pks ->> 'name') is not null,
                true
            )::realtime.wal_column
        )
        from
            jsonb_array_elements(wal -> 'columns') x
            left join jsonb_array_elements(wal -> 'pk') pks
                on (x ->> 'name') = (pks ->> 'name');

    old_columns =
        array_agg(
            (
                x->>'name',
                x->>'type',
                x->>'typeoid',
                realtime.cast(
                    (x->'value') #>> '{}',
                    coalesce(
                        (x->>'typeoid')::regtype, -- null when wal2json version <= 2.4
                        (x->>'type')::regtype
                    )
                ),
                (pks ->> 'name') is not null,
                true
            )::realtime.wal_column
        )
        from
            jsonb_array_elements(wal -> 'identity') x
            left join jsonb_array_elements(wal -> 'pk') pks
                on (x ->> 'name') = (pks ->> 'name');

    for role_record in
        select claims_role
        from (select distinct claims_role from unnest(subscriptions)) t
        order by claims_role::text
    loop
        working_role := role_record.claims_role;

        -- Update `is_selectable` for columns and old_columns (once per role)
        columns =
            array_agg(
                (
                    c.name,
                    c.type_name,
                    c.type_oid,
                    c.value,
                    c.is_pkey,
                    pg_catalog.has_column_privilege(working_role, entity_, c.name, 'SELECT')
                )::realtime.wal_column
            )
            from
                unnest(columns) c;

        old_columns =
                array_agg(
                    (
                        c.name,
                        c.type_name,
                        c.type_oid,
                        c.value,
                        c.is_pkey,
                        pg_catalog.has_column_privilege(working_role, entity_, c.name, 'SELECT')
                    )::realtime.wal_column
                )
                from
                    unnest(old_columns) c;

        if action <> 'DELETE' and count(1) = 0 from unnest(columns) c where c.is_pkey then
            -- Fan out 400 error per distinct selected_columns for this role
            for cols_record in
                select selected_columns
                from (select distinct selected_columns from unnest(subscriptions) s where s.claims_role = working_role) t
                order by coalesce(array_to_string(selected_columns, ','), '')
            loop
                working_selected_columns := cols_record.selected_columns;
                return next (
                    jsonb_build_object(
                        'schema', wal ->> 'schema',
                        'table', wal ->> 'table',
                        'type', action
                    ),
                    is_rls_enabled,
                    (select array_agg(s.subscription_id) from unnest(subscriptions) as s where s.claims_role = working_role and (s.selected_columns is not distinct from working_selected_columns)),
                    array['Error 400: Bad Request, no primary key']
                )::realtime.wal_rls;
            end loop;

        -- The claims role does not have SELECT permission to the primary key of entity
        elsif action <> 'DELETE' and sum(c.is_selectable::int) <> count(1) from unnest(columns) c where c.is_pkey then
            -- Fan out 401 error per distinct selected_columns for this role
            for cols_record in
                select selected_columns
                from (select distinct selected_columns from unnest(subscriptions) s where s.claims_role = working_role) t
                order by coalesce(array_to_string(selected_columns, ','), '')
            loop
                working_selected_columns := cols_record.selected_columns;
                return next (
                    jsonb_build_object(
                        'schema', wal ->> 'schema',
                        'table', wal ->> 'table',
                        'type', action
                    ),
                    is_rls_enabled,
                    (select array_agg(s.subscription_id) from unnest(subscriptions) as s where s.claims_role = working_role and (s.selected_columns is not distinct from working_selected_columns)),
                    array['Error 401: Unauthorized']
                )::realtime.wal_rls;
            end loop;

        else
            -- Create the prepared statement (once per role)
            if is_rls_enabled and action <> 'DELETE' then
                if (select 1 from pg_prepared_statements where name = 'walrus_rls_stmt' limit 1) > 0 then
                    deallocate walrus_rls_stmt;
                end if;
                execute realtime.build_prepared_statement_sql('walrus_rls_stmt', entity_, columns);
            end if;

            -- Collect all visible subscription IDs for this role (filter check + RLS check)
            visible_role_sub_ids = '{}';

            for subscription_id, claims in (
                    select
                        subs.subscription_id,
                        subs.claims
                    from
                        unnest(subscriptions) subs
                    where
                        subs.entity = entity_
                        and subs.claims_role = working_role
                        and (
                            realtime.is_visible_through_filters(columns, subs.filters)
                            or (
                              action = 'DELETE'
                              and realtime.is_visible_through_filters(old_columns, subs.filters)
                            )
                        )
            ) loop

                if not is_rls_enabled or action = 'DELETE' then
                    visible_role_sub_ids = visible_role_sub_ids || subscription_id;
                else
                    -- Check if RLS allows the role to see the record
                    perform
                        -- Trim leading and trailing quotes from working_role because set_config
                        -- doesn't recognize the role as valid if they are included
                        set_config('role', trim(both '"' from working_role::text), true),
                        set_config('request.jwt.claims', claims::text, true);

                    execute 'execute walrus_rls_stmt' into subscription_has_access;

                    -- Reset the role on every FOR..LOOP batch execution.
                    -- The first batch of 10 rows is pre-fetched using the current connection role (PG internal behaviour)
                    -- then we have to reset it again otherwise it would use the role defined in the `set_config` above
                    -- to fetch the remaining rows when rows>10, which could be a user-defined role that lacks execution grants.
                    -- The flow is:
                    --   1. run batch with conn role
                    --   2. set_config working_role
                    --   3. execute walrus
                    --   4. reset role (revert)
                    --   5. repeat
                    perform set_config('role', null, true);

                    if subscription_has_access then
                        visible_role_sub_ids = visible_role_sub_ids || subscription_id;
                    end if;
                end if;
            end loop;

            perform set_config('role', null, true);

            -- Inner loop: per distinct selected_columns for this role
            for cols_record in
                select selected_columns
                from (select distinct selected_columns from unnest(subscriptions) s where s.claims_role = working_role) t
                order by coalesce(array_to_string(selected_columns, ','), '')
            loop
                working_selected_columns := cols_record.selected_columns;

                output = jsonb_build_object(
                    'schema', wal ->> 'schema',
                    'table', wal ->> 'table',
                    'type', action,
                    'commit_timestamp', to_char(
                        ((wal ->> 'timestamp')::timestamptz at time zone 'utc'),
                        'YYYY-MM-DD"T"HH24:MI:SS.MS"Z"'
                    ),
                    'columns', (
                        select
                            jsonb_agg(
                                jsonb_build_object(
                                    'name', pa.attname,
                                    'type', pt.typname
                                )
                                order by pa.attnum asc
                            )
                        from
                            pg_attribute pa
                            join pg_type pt
                                on pa.atttypid = pt.oid
                            left join (
                                select unnest(conkey) as pkey_attnum
                                from pg_constraint
                                where conrelid = entity_ and contype = 'p'
                            ) pk on pk.pkey_attnum = pa.attnum
                        where
                            attrelid = entity_
                            and attnum > 0
                            and pg_catalog.has_column_privilege(working_role, entity_, pa.attname, 'SELECT')
                            and (working_selected_columns is null or pa.attname = any(working_selected_columns) or pk.pkey_attnum is not null)
                    )
                )
                -- Add "record" key for insert and update
                || case
                    when action in ('INSERT', 'UPDATE') then
                        jsonb_build_object(
                            'record',
                            (
                                select
                                    jsonb_object_agg(
                                        -- if unchanged toast, get column name and value from old record
                                        coalesce((c).name, (oc).name),
                                        case
                                            when (c).name is null then (oc).value
                                            else (c).value
                                        end
                                    )
                                from
                                    unnest(columns) c
                                    full outer join unnest(old_columns) oc
                                        on (c).name = (oc).name
                                where
                                    coalesce((c).is_selectable, (oc).is_selectable)
                                    and (working_selected_columns is null or coalesce((c).name, (oc).name) = any(working_selected_columns) or coalesce((c).is_pkey, (oc).is_pkey))
                                    and ( not error_record_exceeds_max_size or (octet_length((c).value::text) <= 64))
                            )
                        )
                    else '{}'::jsonb
                end
                -- Add "old_record" key for update and delete
                || case
                    when action = 'UPDATE' then
                        jsonb_build_object(
                                'old_record',
                                (
                                    select jsonb_object_agg((c).name, (c).value)
                                    from unnest(old_columns) c
                                    where
                                        (c).is_selectable
                                        and (working_selected_columns is null or (c).name = any(working_selected_columns) or (c).is_pkey)
                                        and ( not error_record_exceeds_max_size or (octet_length((c).value::text) <= 64))
                                )
                            )
                    when action = 'DELETE' then
                        jsonb_build_object(
                            'old_record',
                            (
                                select jsonb_object_agg((c).name, (c).value)
                                from unnest(old_columns) c
                                where
                                    (c).is_selectable
                                    and (working_selected_columns is null or (c).name = any(working_selected_columns) or (c).is_pkey)
                                    and ( not error_record_exceeds_max_size or (octet_length((c).value::text) <= 64))
                                    and ( not is_rls_enabled or (c).is_pkey ) -- if RLS enabled, we can't secure deletes so filter to pkey
                            )
                        )
                    else '{}'::jsonb
                end;

                -- Filter visible_role_sub_ids to those matching the current selected_columns group
                visible_to_subscription_ids = coalesce(
                    (
                        select array_agg(s.subscription_id)
                        from unnest(subscriptions) s
                        where s.claims_role = working_role
                          and (s.selected_columns is not distinct from working_selected_columns)
                          and s.subscription_id = any(visible_role_sub_ids)
                    ),
                    '{}'::uuid[]
                );

                return next (
                    output,
                    is_rls_enabled,
                    visible_to_subscription_ids,
                    case
                        when error_record_exceeds_max_size then array['Error 413: Payload Too Large']
                        else '{}'
                    end
                )::realtime.wal_rls;
            end loop;

        end if;
    end loop;

    perform set_config('role', null, true);
end;
$$;


ALTER FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) OWNER TO supabase_realtime_admin;

--
-- Name: broadcast_changes(text, text, text, text, text, record, record, text); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text DEFAULT 'ROW'::text) RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    -- Declare a variable to hold the JSONB representation of the row
    row_data jsonb := '{}'::jsonb;
BEGIN
    IF level = 'STATEMENT' THEN
        RAISE EXCEPTION 'function can only be triggered for each row, not for each statement';
    END IF;
    -- Check the operation type and handle accordingly
    IF operation = 'INSERT' OR operation = 'UPDATE' OR operation = 'DELETE' THEN
        row_data := jsonb_build_object('old_record', OLD, 'record', NEW, 'operation', operation, 'table', table_name, 'schema', table_schema);
        PERFORM realtime.send (row_data, event_name, topic_name);
    ELSE
        RAISE EXCEPTION 'Unexpected operation type: %', operation;
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Failed to process the row: %', SQLERRM;
END;

$$;


ALTER FUNCTION realtime.broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text) OWNER TO supabase_realtime_admin;

--
-- Name: build_prepared_statement_sql(text, regclass, realtime.wal_column[]); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) RETURNS text
    LANGUAGE sql
    AS $$
      /*
      Builds a sql string that, if executed, creates a prepared statement to
      tests retrive a row from *entity* by its primary key columns.
      Example
          select realtime.build_prepared_statement_sql('public.notes', '{"id"}'::text[], '{"bigint"}'::text[])
      */
          select
      'prepare ' || prepared_statement_name || ' as
          select
              exists(
                  select
                      1
                  from
                      ' || entity || '
                  where
                      ' || string_agg(quote_ident(pkc.name) || '=' || quote_nullable(pkc.value #>> '{}') , ' and ') || '
              )'
          from
              unnest(columns) pkc
          where
              pkc.is_pkey
          group by
              entity
      $$;


ALTER FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) OWNER TO supabase_realtime_admin;

--
-- Name: cast(text, regtype); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime."cast"(val text, type_ regtype) RETURNS jsonb
    LANGUAGE plpgsql IMMUTABLE
    AS $$
declare
  res jsonb;
begin
  if type_::text = 'bytea' then
    return to_jsonb(val);
  end if;
  execute format('select to_jsonb(%L::'|| type_::text || ')', val) into res;
  return res;
end
$$;


ALTER FUNCTION realtime."cast"(val text, type_ regtype) OWNER TO supabase_realtime_admin;

--
-- Name: check_equality_op(realtime.equality_op, regtype, text, text); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) RETURNS boolean
    LANGUAGE plpgsql IMMUTABLE
    AS $$
/*
Casts *val_1* and *val_2* as type *type_* and check the *op* condition for truthiness
*/
declare
    op_symbol text = (
        case
            when op = 'eq' then '='
            when op = 'neq' then '!='
            when op = 'lt' then '<'
            when op = 'lte' then '<='
            when op = 'gt' then '>'
            when op = 'gte' then '>='
            when op = 'in' then '= any'
            else 'UNKNOWN OP'
        end
    );
    res boolean;
begin
    execute format(
        'select %L::'|| type_::text || ' ' || op_symbol
        || ' ( %L::'
        || (
            case
                when op = 'in' then type_::text || '[]'
                else type_::text end
        )
        || ')', val_1, val_2) into res;
    return res;
end;
$$;


ALTER FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) OWNER TO supabase_realtime_admin;

--
-- Name: check_equality_op(realtime.equality_op, regtype, text, text, boolean); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text, negate boolean) RETURNS boolean
    LANGUAGE plpgsql STABLE
    AS $$
declare
    op_symbol text;
    res boolean;
begin
    -- IS DISTINCT FROM / IS NOT DISTINCT FROM: infix, both sides typed literals
    if op = 'isdistinct' then
        execute format(
            'select %L::%s %s %L::%s',
            val_1,
            type_::text,
            case when negate then 'IS NOT DISTINCT FROM' else 'IS DISTINCT FROM' end,
            val_2,
            type_::text
        ) into res;
        return res;
    end if;

    -- IS requires a keyword RHS (NULL, TRUE, FALSE, UNKNOWN), not a typed literal
    if op = 'is' then
        if val_2 not in ('null', 'true', 'false', 'unknown') then
            raise exception 'invalid value for is filter: must be null, true, false, or unknown';
        end if;
        execute format(
            'select %L::%s %s %s',
            val_1,
            type_::text,
            case when negate then 'IS NOT' else 'IS' end,
            upper(val_2)
        ) into res;
        return res;
    end if;

    op_symbol = case
        when op = 'eq'    then '='
        when op = 'neq'   then '!='
        when op = 'lt'    then '<'
        when op = 'lte'   then '<='
        when op = 'gt'    then '>'
        when op = 'gte'   then '>='
        when op = 'in'    then '= any'
        when op = 'like'   then 'LIKE'
        when op = 'ilike'  then 'ILIKE'
        when op = 'match'  then '~'
        when op = 'imatch' then '~*'
        else null
    end;

    if op_symbol is null then
        raise exception 'unsupported equality operator: %', op::text;
    end if;

    execute format(
        'select %L::%s %s (%L::%s)',
        val_1,
        type_::text,
        op_symbol,
        val_2,
        case when op = 'in' then type_::text || '[]' else type_::text end
    ) into res;

    return case when negate then not res else res end;
end;
$$;


ALTER FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text, negate boolean) OWNER TO supabase_realtime_admin;

--
-- Name: is_visible_through_filters(realtime.wal_column[], realtime.user_defined_filter[]); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) RETURNS boolean
    LANGUAGE sql STABLE
    AS $$
    select
        filters is null
        or array_length(filters, 1) is null
        or coalesce(
            count(col.name) = count(1)
            and sum(
                realtime.check_equality_op(
                    op:=f.op,
                    type_:=coalesce(col.type_oid::regtype, col.type_name::regtype),
                    val_1:=col.value #>> '{}',
                    val_2:=f.value,
                    negate:=coalesce(f.negate, false)
                )::int
            ) filter (where col.name is not null) = count(col.name),
            false
        )
    from
        unnest(filters) f
        left join unnest(columns) col
            on f.column_name = col.name;
$$;


ALTER FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) OWNER TO supabase_realtime_admin;

--
-- Name: list_changes(name, name, integer, integer); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) RETURNS TABLE(wal jsonb, is_rls_enabled boolean, subscription_ids uuid[], errors text[], slot_changes_count bigint)
    LANGUAGE sql
    SET log_min_messages TO 'fatal'
    AS $$
  WITH pub AS (
    SELECT
      concat_ws(
        ',',
        CASE WHEN bool_or(pubinsert) THEN 'insert' ELSE NULL END,
        CASE WHEN bool_or(pubupdate) THEN 'update' ELSE NULL END,
        CASE WHEN bool_or(pubdelete) THEN 'delete' ELSE NULL END
      ) AS w2j_actions,
      coalesce(
        string_agg(
          realtime.quote_wal2json(format('%I.%I', schemaname, tablename)::regclass),
          ','
        ) filter (WHERE ppt.tablename IS NOT NULL),
        ''
      ) AS w2j_add_tables
    FROM pg_publication pp
    LEFT JOIN pg_publication_tables ppt ON pp.pubname = ppt.pubname
    WHERE pp.pubname = publication
    GROUP BY pp.pubname
    LIMIT 1
  ),
  -- MATERIALIZED ensures pg_logical_slot_get_changes is called exactly once
  w2j AS MATERIALIZED (
    SELECT x.*, pub.w2j_add_tables
    FROM pub,
         pg_logical_slot_get_changes(
           slot_name, null, max_changes,
           'include-pk', 'true',
           'include-transaction', 'false',
           'include-timestamp', 'true',
           'include-type-oids', 'true',
           'format-version', '2',
           'actions', pub.w2j_actions,
           'add-tables', pub.w2j_add_tables
         ) x
  ),
  slot_count AS (
    SELECT count(*)::bigint AS cnt
    FROM w2j
    WHERE w2j.w2j_add_tables <> ''
  ),
  rls_filtered AS (
    SELECT xyz.wal, xyz.is_rls_enabled, xyz.subscription_ids, xyz.errors
    FROM w2j,
         realtime.apply_rls(
           wal := w2j.data::jsonb,
           max_record_bytes := max_record_bytes
         ) xyz(wal, is_rls_enabled, subscription_ids, errors)
    WHERE w2j.w2j_add_tables <> ''
      AND xyz.subscription_ids[1] IS NOT NULL
  )
  SELECT rf.wal, rf.is_rls_enabled, rf.subscription_ids, rf.errors, sc.cnt
  FROM rls_filtered rf, slot_count sc

  UNION ALL

  SELECT null, null, null, null, sc.cnt
  FROM slot_count sc
  WHERE NOT EXISTS (SELECT 1 FROM rls_filtered)
$$;


ALTER FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) OWNER TO supabase_realtime_admin;

--
-- Name: list_changes_sync(name, name, integer, integer); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.list_changes_sync(publication name, slot_name name, max_changes integer, max_record_bytes integer) RETURNS TABLE(wal jsonb, is_rls_enabled boolean, subscription_ids uuid[], errors text[], slot_changes_count bigint)
    LANGUAGE sql
    SET log_min_messages TO 'fatal'
    AS $$
  WITH pub AS (
    SELECT
      concat_ws(
        ',',
        CASE WHEN bool_or(pubinsert) THEN 'insert' ELSE NULL END,
        CASE WHEN bool_or(pubupdate) THEN 'update' ELSE NULL END,
        CASE WHEN bool_or(pubdelete) THEN 'delete' ELSE NULL END
      ) AS w2j_actions,
      coalesce(
        string_agg(
          realtime.quote_wal2json(format('%I.%I', schemaname, tablename)::regclass),
          ','
        ) filter (WHERE ppt.tablename IS NOT NULL),
        ''
      ) AS w2j_add_tables
    FROM pg_publication pp
    LEFT JOIN pg_publication_tables ppt ON pp.pubname = ppt.pubname
    WHERE pp.pubname = publication
    GROUP BY pp.pubname
    LIMIT 1
  ),
  -- MATERIALIZED ensures the slot is read exactly once.
  consumed AS MATERIALIZED (
    SELECT x.*, pub.w2j_add_tables
    FROM pub,
         realtime.settled_changes(
           slot_name, max_changes,
           'include-pk', 'true',
           'include-transaction', 'false',
           'include-timestamp', 'true',
           'include-type-oids', 'true',
           'format-version', '2',
           'actions', pub.w2j_actions,
           'add-tables', pub.w2j_add_tables
         ) x
  ),
  slot_count AS (
    SELECT count(*)::bigint AS cnt
    FROM consumed
    WHERE consumed.w2j_add_tables <> ''
  ),
  rls_filtered AS (
    SELECT xyz.wal, xyz.is_rls_enabled, xyz.subscription_ids, xyz.errors
    FROM consumed,
         realtime.apply_rls(
           wal := consumed.data::jsonb,
           max_record_bytes := max_record_bytes
         ) xyz(wal, is_rls_enabled, subscription_ids, errors)
    WHERE consumed.w2j_add_tables <> ''
      AND xyz.subscription_ids[1] IS NOT NULL
  )
  SELECT rf.wal, rf.is_rls_enabled, rf.subscription_ids, rf.errors, sc.cnt
  FROM rls_filtered rf, slot_count sc

  UNION ALL

  SELECT null, null, null, null, sc.cnt
  FROM slot_count sc
  WHERE NOT EXISTS (SELECT 1 FROM rls_filtered)
$$;


ALTER FUNCTION realtime.list_changes_sync(publication name, slot_name name, max_changes integer, max_record_bytes integer) OWNER TO supabase_realtime_admin;

--
-- Name: quote_wal2json(regclass); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.quote_wal2json(entity regclass) RETURNS text
    LANGUAGE sql IMMUTABLE STRICT
    AS $$
  SELECT
    realtime.wal2json_escape_identifier(nsp.nspname::text)
    || '.'
    || realtime.wal2json_escape_identifier(pc.relname::text)
  FROM pg_class pc
  JOIN pg_namespace nsp ON pc.relnamespace = nsp.oid
  WHERE pc.oid = entity
$$;


ALTER FUNCTION realtime.quote_wal2json(entity regclass) OWNER TO supabase_realtime_admin;

--
-- Name: send(jsonb, text, text, boolean); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.send(payload jsonb, event text, topic text, private boolean DEFAULT true) RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
  generated_id uuid;
  final_payload jsonb;
BEGIN
  BEGIN
    generated_id := gen_random_uuid();

    -- Check if payload has an 'id' key, if not, add the generated UUID
    IF payload ? 'id' THEN
      final_payload := payload;
    ELSE
      final_payload := jsonb_set(payload, '{id}', to_jsonb(generated_id));
    END IF;

    -- Set the topic configuration
    EXECUTE format('SET LOCAL realtime.topic TO %L', topic);

    INSERT INTO realtime.messages (id, payload, event, topic, private, extension)
    VALUES (generated_id, final_payload, event, topic, private, 'broadcast');
  EXCEPTION
    WHEN OTHERS THEN
      RAISE WARNING 'WarnSendingBroadcastMessage: %', SQLERRM;
  END;
END;
$$;


ALTER FUNCTION realtime.send(payload jsonb, event text, topic text, private boolean) OWNER TO supabase_realtime_admin;

--
-- Name: send_binary(bytea, text, text, boolean); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.send_binary(payload bytea, event text, topic text, private boolean DEFAULT true) RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
  generated_id uuid;
BEGIN
  BEGIN
    generated_id := gen_random_uuid();

    EXECUTE format('SET LOCAL realtime.topic TO %L', topic);

    INSERT INTO realtime.messages (id, binary_payload, event, topic, private, extension)
    VALUES (generated_id, payload, event, topic, private, 'broadcast');
  EXCEPTION
    WHEN OTHERS THEN
      RAISE WARNING 'WarnSendingBroadcastMessage: %', SQLERRM;
  END;
END;
$$;


ALTER FUNCTION realtime.send_binary(payload bytea, event text, topic text, private boolean) OWNER TO supabase_realtime_admin;

--
-- Name: settled_changes(name, integer, text[]); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.settled_changes(slot_name name, max_changes integer, VARIADIC opts text[]) RETURNS TABLE(lsn pg_lsn, xid xid, data text)
    LANGUAGE plpgsql
    AS $$
declare
  upto pg_lsn;
  total bigint;
  xids xid[];
  starts bigint[];
  snapshot pg_snapshot;
  running_xids xid[];
  xmax_age int;
  cut bigint;
begin
  -- Each statement in a volatile function takes its own snapshot, which is what lets the
  -- check below see a writer that was still in flight when the peek ran. Under REPEATABLE
  -- READ the snapshot never advances, so a deferred change would never be released.
  if current_setting('transaction_isolation') <> 'read committed' then
    raise exception 'realtime.settled_changes requires READ COMMITTED';
  end if;

  -- The peek and the read below cover the same WAL, so the read cannot reach a commit the
  -- check never saw.
  upto := pg_current_wal_flush_lsn();

  -- One entry per transaction, in commit order: its xid and the position of its first
  -- change. The peek uses the caller's own options, so max_changes counts exactly what the
  -- read counts. A non-transactional logical message is emitted as soon as it is decoded,
  -- tagged with the xid of whatever transaction wrote it, so it does not mark where that
  -- transaction starts.
  select coalesce(sum(g.n), 0),
         array_agg(g.x order by g.first) filter (where g.first is not null),
         array_agg(g.first order by g.first) filter (where g.first is not null)
    into total, xids, starts
    from (
      select p.xid as x, count(*) as n,
             min(p.ord) filter (where not case
               when starts_with(p.data, '{"action":"M"') then (p.data::jsonb->>'transactional')::boolean is false
               else false
             end) as first
      from pg_logical_slot_peek_changes(slot_name, upto, max_changes, variadic opts)
           with ordinality as p(lsn, xid, data, ord)
      group by p.xid
    ) g;

  -- Nothing for the caller, but the slot still has to move past what the peek covered.
  if total = 0 then
    perform pg_replication_slot_advance(slot_name, upto);
    return;
  end if;

  if xids is not null then
    -- Taken after the peek is materialized, so a writer that was still in flight during
    -- decoding is guaranteed to show up here.
    snapshot := pg_current_snapshot();

    -- A commit record reaches the WAL before the writer leaves the proc array, so a change
    -- can be decoded while its row is invisible. apply_rls would resolve a policy against a
    -- row it cannot see and authorize it for nobody, while the read consumed it regardless.
    --
    -- xip lists transactions running when the snapshot was taken. It does not cover a writer
    -- whose xid sits at or beyond xmax, which never appears there, so the horizon is checked
    -- too. age() counts backwards from the current xid and so compares correctly across
    -- wraparound.
    select coalesce(array_agg(running.x::xid), array[]::xid[])
      into running_xids
      from pg_snapshot_xip(snapshot) running(x);
    xmax_age := age(pg_snapshot_xmax(snapshot)::xid);

    select min(u.s) into cut
      from unnest(xids, starts) as u(x, s)
      where u.x = any(running_xids) or age(u.x) <= xmax_age;
  end if;

  -- The read stops right after the commit that brings its count to upto_nchanges, so the
  -- count of changes in front of the first unsettled transaction stops it just before that
  -- transaction.
  if cut is null then
    return query
      select p.* from pg_logical_slot_get_changes(slot_name, upto, max_changes, variadic opts) p;
  elsif cut > 1 then
    return query
      select p.* from pg_logical_slot_get_changes(slot_name, upto, (cut - 1)::int, variadic opts) p;
  end if;
end;
$$;


ALTER FUNCTION realtime.settled_changes(slot_name name, max_changes integer, VARIADIC opts text[]) OWNER TO supabase_realtime_admin;

--
-- Name: subscription_check_filters(); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.subscription_check_filters() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
declare
    col_names text[] = coalesce(
            array_agg(a.attname order by a.attnum),
            '{}'::text[]
        )
        from
            pg_catalog.pg_attribute a
        where
            a.attrelid = new.entity
            and a.attnum > 0
            and not a.attisdropped
            and pg_catalog.has_column_privilege(
                (new.claims ->> 'role'),
                a.attrelid,
                a.attnum,
                'SELECT'
            );
    filter realtime.user_defined_filter;
    col_type regtype;
    in_val jsonb;
    selected_col text;
begin
    for filter in select * from unnest(new.filters) loop
        if not filter.column_name = any(col_names) then
            raise exception 'invalid column for filter %', filter.column_name;
        end if;

        col_type = (
            select atttypid::regtype
            from pg_catalog.pg_attribute
            where attrelid = new.entity
                  and attname = filter.column_name
        );
        if col_type is null then
            raise exception 'failed to lookup type for column %', filter.column_name;
        end if;

        if filter.op = 'in'::realtime.equality_op then
            in_val = realtime.cast(filter.value, (col_type::text || '[]')::regtype);
            if coalesce(jsonb_array_length(in_val), 0) > 100 then
                raise exception 'too many values for `in` filter. Maximum 100';
            end if;
        elsif filter.op = 'is'::realtime.equality_op then
            -- `is` requires a keyword RHS rather than a typed literal
            if filter.value not in ('null', 'true', 'false', 'unknown') then
                raise exception 'invalid value for is filter: must be null, true, false, or unknown';
            end if;
            -- IS NULL works for any type, but IS TRUE/FALSE/UNKNOWN require a boolean
            -- operand. Reject the non-null keywords on non-boolean columns here so they
            -- don't abort apply_rls at WAL time.
            if filter.value <> 'null' and col_type <> 'boolean'::regtype then
                raise exception 'is % filter requires a boolean column, got %', filter.value, col_type::text;
            end if;
        elsif filter.op in ('like'::realtime.equality_op, 'ilike'::realtime.equality_op) then
            -- like/ilike apply the text pattern operator (~~); reject column types that
            -- have no such operator instead of failing at WAL time
            if not exists (
                select 1 from pg_catalog.pg_operator
                where oprname = '~~' and oprleft = col_type
            ) then
                raise exception 'operator % requires a text-compatible column type, got %', filter.op::text, col_type::text;
            end if;
        elsif filter.op in ('match'::realtime.equality_op, 'imatch'::realtime.equality_op) then
            -- match/imatch apply the regex operators ~ / ~*; reject column types that have
            -- no such operator (e.g. integer) instead of failing at WAL time, mirroring the
            -- like/ilike guard above.
            if not exists (
                select 1 from pg_catalog.pg_operator
                where oprname = case when filter.op = 'imatch'::realtime.equality_op then '~*' else '~' end
                  and oprleft = col_type
                  and oprright = col_type
                  and oprresult = 'boolean'::regtype
            ) then
                raise exception 'operator % requires a text-compatible column type, got %', filter.op::text, col_type::text;
            end if;
            -- validate the regex eagerly so a bad pattern is rejected here, not inside
            -- apply_rls where it would abort the WAL stream for the entity
            begin
                perform '' ~ filter.value;
            exception when others then
                raise exception 'invalid regular expression for % filter: %', filter.op::text, sqlerrm;
            end;
        else
            -- eq/neq/lt/lte/gt/gte: value must be coercable to the type
            perform realtime.cast(filter.value, col_type);
        end if;
    end loop;

    if new.selected_columns is not null then
        for selected_col in select * from unnest(new.selected_columns) loop
            if not selected_col = any(col_names) then
                raise exception 'invalid column for select %', selected_col;
            end if;
        end loop;
    end if;

    -- Apply consistent order to filters so the unique constraint can't be tricked by a
    -- different filter order. negate is part of the sort key.
    new.filters = coalesce(
        array_agg(f order by f.column_name, f.op, f.value, f.negate),
        '{}'
    ) from unnest(new.filters) f;

    -- Normalize selected_columns order so ARRAY['a','b'] and ARRAY['b','a'] are treated
    -- as the same subscription group in apply_rls. Preserve an empty array as '{}'
    -- ("primary keys only") so it stays distinct from NULL ("all columns"); array_agg
    -- over an empty set would otherwise collapse '{}' back to NULL.
    if new.selected_columns is not null then
        new.selected_columns = coalesce(
            (
                select array_agg(c order by c)
                from unnest(new.selected_columns) c
            ),
            '{}'::text[]
        );
    end if;

    return new;
end;
$$;


ALTER FUNCTION realtime.subscription_check_filters() OWNER TO supabase_realtime_admin;

--
-- Name: to_regrole(text); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.to_regrole(role_name text) RETURNS regrole
    LANGUAGE sql IMMUTABLE
    AS $$ select role_name::regrole $$;


ALTER FUNCTION realtime.to_regrole(role_name text) OWNER TO supabase_realtime_admin;

--
-- Name: topic(); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.topic() RETURNS text
    LANGUAGE sql STABLE
    AS $$
select nullif(current_setting('realtime.topic', true), '')::text;
$$;


ALTER FUNCTION realtime.topic() OWNER TO supabase_realtime_admin;

--
-- Name: wal2json_escape_identifier(text); Type: FUNCTION; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE FUNCTION realtime.wal2json_escape_identifier(name text) RETURNS text
    LANGUAGE sql IMMUTABLE STRICT
    AS $$
  -- Prefix `\`, `,`, `.`, and any whitespace with `\`
  SELECT regexp_replace(name, '([\\,.[:space:]])', '\\\1', 'g')
$$;


ALTER FUNCTION realtime.wal2json_escape_identifier(name text) OWNER TO supabase_realtime_admin;

--
-- Name: allow_any_operation(text[]); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.allow_any_operation(expected_operations text[]) RETURNS boolean
    LANGUAGE sql STABLE
    AS $$
  WITH current_operation AS (
    SELECT storage.operation() AS raw_operation
  ),
  normalized AS (
    SELECT CASE
      WHEN raw_operation LIKE 'storage.%' THEN substr(raw_operation, 9)
      ELSE raw_operation
    END AS current_operation
    FROM current_operation
  )
  SELECT EXISTS (
    SELECT 1
    FROM normalized n
    CROSS JOIN LATERAL unnest(expected_operations) AS expected_operation
    WHERE expected_operation IS NOT NULL
      AND expected_operation <> ''
      AND n.current_operation = CASE
        WHEN expected_operation LIKE 'storage.%' THEN substr(expected_operation, 9)
        ELSE expected_operation
      END
  );
$$;


ALTER FUNCTION storage.allow_any_operation(expected_operations text[]) OWNER TO supabase_storage_admin;

--
-- Name: allow_only_operation(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.allow_only_operation(expected_operation text) RETURNS boolean
    LANGUAGE sql STABLE
    AS $$
  WITH current_operation AS (
    SELECT storage.operation() AS raw_operation
  ),
  normalized AS (
    SELECT
      CASE
        WHEN raw_operation LIKE 'storage.%' THEN substr(raw_operation, 9)
        ELSE raw_operation
      END AS current_operation,
      CASE
        WHEN expected_operation LIKE 'storage.%' THEN substr(expected_operation, 9)
        ELSE expected_operation
      END AS requested_operation
    FROM current_operation
  )
  SELECT CASE
    WHEN requested_operation IS NULL OR requested_operation = '' THEN FALSE
    ELSE COALESCE(current_operation = requested_operation, FALSE)
  END
  FROM normalized;
$$;


ALTER FUNCTION storage.allow_only_operation(expected_operation text) OWNER TO supabase_storage_admin;

--
-- Name: can_insert_object(text, text, uuid, jsonb); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.can_insert_object(bucketid text, name text, owner uuid, metadata jsonb) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  INSERT INTO "storage"."objects" ("bucket_id", "name", "owner", "metadata") VALUES (bucketid, name, owner, metadata);
  -- hack to rollback the successful insert
  RAISE sqlstate 'PT200' using
  message = 'ROLLBACK',
  detail = 'rollback successful insert';
END
$$;


ALTER FUNCTION storage.can_insert_object(bucketid text, name text, owner uuid, metadata jsonb) OWNER TO supabase_storage_admin;

--
-- Name: enforce_bucket_lifecycle_service_role(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.enforce_bucket_lifecycle_service_role() RETURNS trigger
    LANGUAGE plpgsql
    SET search_path TO 'pg_catalog'
    AS $$
BEGIN
  IF current_user::text IS DISTINCT FROM TG_ARGV[0]
     AND (
       OLD.lifecycle_configuration IS DISTINCT FROM NEW.lifecycle_configuration
       OR OLD.lifecycle_configuration_generation IS DISTINCT FROM NEW.lifecycle_configuration_generation
     ) THEN
    -- AFTER runs only after caller RLS has accepted the proposed row. The API
    -- recognizes this specific error after rolling back its permission probe;
    -- direct non-service writes still fail and cannot persist the change.
    RAISE EXCEPTION 'bucket control columns may only be changed by the configured storage service role'
      USING ERRCODE = 'PST01',
            SCHEMA = TG_TABLE_SCHEMA,
            TABLE = TG_TABLE_NAME,
            CONSTRAINT = TG_NAME;
  END IF;

  RETURN NULL;
END;
$$;


ALTER FUNCTION storage.enforce_bucket_lifecycle_service_role() OWNER TO supabase_storage_admin;

--
-- Name: enforce_bucket_name_length(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.enforce_bucket_name_length() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
begin
    if length(new.name) > 100 then
        raise exception 'bucket name "%" is too long (% characters). Max is 100.', new.name, length(new.name);
    end if;
    return new;
end;
$$;


ALTER FUNCTION storage.enforce_bucket_name_length() OWNER TO supabase_storage_admin;

--
-- Name: extension(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.extension(name text) RETURNS text
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE
    _parts text[];
    _filename text;
BEGIN
    -- Split on "/" to get path segments
    SELECT string_to_array(name, '/') INTO _parts;
    -- Get the last path segment (the actual filename)
    SELECT _parts[array_length(_parts, 1)] INTO _filename;
    -- Extract extension: reverse, split on '.', then reverse again
    RETURN reverse(split_part(reverse(_filename), '.', 1));
END
$$;


ALTER FUNCTION storage.extension(name text) OWNER TO supabase_storage_admin;

--
-- Name: filename(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.filename(name text) RETURNS text
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE
    _parts text[];
BEGIN
    SELECT string_to_array(name, '/') INTO _parts;
    RETURN _parts[array_length(_parts, 1)];
END
$$;


ALTER FUNCTION storage.filename(name text) OWNER TO supabase_storage_admin;

--
-- Name: foldername(text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.foldername(name text) RETURNS text[]
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE
    _parts text[];
BEGIN
    -- Split on "/" to get path segments
    SELECT string_to_array(name, '/') INTO _parts;
    -- Return everything except the last segment
    RETURN _parts[1 : array_length(_parts,1) - 1];
END
$$;


ALTER FUNCTION storage.foldername(name text) OWNER TO supabase_storage_admin;

--
-- Name: get_common_prefix(text, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.get_common_prefix(p_key text, p_prefix text, p_delimiter text) RETURNS text
    LANGUAGE sql IMMUTABLE
    AS $$
SELECT CASE
    WHEN p_delimiter <> ''
         AND position(p_delimiter IN substring(p_key FROM length(p_prefix) + 1)) > 0
    THEN left(
        p_key,
        length(p_prefix)
            + position(p_delimiter IN substring(p_key FROM length(p_prefix) + 1))
            + length(p_delimiter) - 1
    )
    ELSE NULL
END;
$$;


ALTER FUNCTION storage.get_common_prefix(p_key text, p_prefix text, p_delimiter text) OWNER TO supabase_storage_admin;

--
-- Name: get_size_by_bucket(text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.get_size_by_bucket(noncurrent_versions text DEFAULT 'include'::text, delete_markers text DEFAULT 'include'::text) RETURNS TABLE(size bigint, bucket_id text)
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
    -- COALESCE first: NULL NOT IN (...) evaluates to NULL (not TRUE), so a
    -- bare NOT IN check silently leaves an explicit NULL argument unreset.
    noncurrent_versions := COALESCE(noncurrent_versions, 'include');
    delete_markers := COALESCE(delete_markers, 'include');
    IF noncurrent_versions NOT IN ('exclude', 'only', 'include') THEN
        noncurrent_versions := 'include';
    END IF;
    IF delete_markers NOT IN ('exclude', 'only', 'include') THEN
        delete_markers := 'include';
    END IF;

    return query
        select sum((metadata->>'size')::bigint)::bigint as size, obj.bucket_id
        from "storage".objects as obj
        where (noncurrent_versions != 'exclude' OR obj.archived_at IS NULL)
          and (noncurrent_versions != 'only' OR obj.archived_at IS NOT NULL)
          and (delete_markers != 'exclude' OR NOT obj.is_delete_marker)
          and (delete_markers != 'only' OR obj.is_delete_marker)
        group by obj.bucket_id;
END
$$;


ALTER FUNCTION storage.get_size_by_bucket(noncurrent_versions text, delete_markers text) OWNER TO supabase_storage_admin;

--
-- Name: list_multipart_uploads_with_delimiter(text, text, text, integer, text, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.list_multipart_uploads_with_delimiter(bucket_id text, prefix_param text, delimiter_param text, max_keys integer DEFAULT 100, next_key_token text DEFAULT ''::text, next_upload_token text DEFAULT ''::text, raw_prefix_param text DEFAULT NULL::text) RETURNS TABLE(key text, id text, created_at timestamp with time zone)
    LANGUAGE sql STABLE
    AS $_$
WITH candidates AS (
    SELECT
        upload.key AS object_key,
        CASE
            WHEN position($3 IN substring(upload.key FROM length(coalesce($7, $2)) + 1)) > 0
            THEN left(
                upload.key,
                length(coalesce($7, $2))
                    + position($3 IN substring(upload.key FROM length(coalesce($7, $2)) + 1))
                    + length($3) - 1
            )
            ELSE upload.key
        END AS result_key,
        upload.id,
        upload.created_at,
        position($3 IN substring(upload.key FROM length(coalesce($7, $2)) + 1)) > 0 AS is_common_prefix
    FROM storage.s3_multipart_uploads AS upload
    WHERE upload.bucket_id = $1
      AND upload.key COLLATE "C" LIKE $2 || '%'
), filtered AS (
    SELECT candidate.*
    FROM candidates AS candidate
    WHERE $5 = ''
       OR candidate.result_key COLLATE "C" > $5
       OR (
           candidate.result_key COLLATE "C" = $5
           AND NOT candidate.is_common_prefix
           AND $6 <> ''
           -- A completed or aborted marker repeats the remaining same-key uploads.
           AND COALESCE(
               (candidate.created_at, candidate.id COLLATE "C") > (
                   SELECT marker.created_at, marker.id COLLATE "C"
                   FROM storage.s3_multipart_uploads AS marker
                   WHERE marker.bucket_id = $1
                     AND marker.key COLLATE "C" = $5
                     AND marker.id = $6
               ),
               TRUE
           )
       )
), ranked AS (
    SELECT
        filtered.*,
        row_number() OVER (
            PARTITION BY filtered.result_key COLLATE "C"
            ORDER BY filtered.created_at, filtered.id COLLATE "C"
        ) AS prefix_rank
    FROM filtered
)
SELECT ranked.result_key, ranked.id, ranked.created_at
FROM ranked
WHERE NOT ranked.is_common_prefix OR ranked.prefix_rank = 1
ORDER BY ranked.result_key COLLATE "C", ranked.created_at, ranked.id COLLATE "C"
LIMIT $4;
$_$;


ALTER FUNCTION storage.list_multipart_uploads_with_delimiter(bucket_id text, prefix_param text, delimiter_param text, max_keys integer, next_key_token text, next_upload_token text, raw_prefix_param text) OWNER TO supabase_storage_admin;

--
-- Name: list_objects_with_delimiter(text, text, text, integer, text, text, text, text, text, timestamp with time zone, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.list_objects_with_delimiter(_bucket_id text, prefix_param text, delimiter_param text, max_keys integer DEFAULT 100, start_after text DEFAULT ''::text, next_token text DEFAULT ''::text, sort_order text DEFAULT 'asc'::text, noncurrent_versions text DEFAULT 'exclude'::text, delete_markers text DEFAULT 'exclude'::text, next_token_archived_at timestamp with time zone DEFAULT NULL::timestamp with time zone, next_token_version text DEFAULT ''::text) RETURNS TABLE(name text, id uuid, metadata jsonb, updated_at timestamp with time zone, created_at timestamp with time zone, last_accessed_at timestamp with time zone, version text, archived_at timestamp with time zone, is_delete_marker boolean, is_versioned boolean)
    LANGUAGE plpgsql STABLE
    AS $_$
DECLARE
    v_peek_name TEXT;
    v_current RECORD;
    v_common_prefix TEXT;

    -- Configuration
    v_is_asc BOOLEAN;
    v_prefix TEXT;
    v_start TEXT;
    v_start_relative TEXT;
    v_upper_bound TEXT;
    v_file_batch_size INT;
    v_version_filter TEXT;

    -- true when noncurrent_versions can return >1 row per name; keeps them
    -- ordered most-recent-first and lets pagination resume mid-key
    v_multi_row BOOLEAN;
    v_name_order TEXT;
    v_exact_range_predicate TEXT;
    v_strict_range_predicate TEXT;
    v_inclusive_range_predicate TEXT;

    -- Seek state for the current name. archived_at is normalized to JavaScript's
    -- millisecond precision and version breaks ties within the same millisecond.
    -- Current rows use 'infinity'; NULL means no tiebreak has been established.
    v_next_seek TEXT;
    v_next_seek_at TIMESTAMPTZ;
    v_next_seek_version TEXT;
    v_next_seek_strict BOOLEAN := false;
    v_cursor_is_folder BOOLEAN;
    v_count INT := 0;
    v_previous_seek TEXT;
    v_previous_seek_at TIMESTAMPTZ;
    v_previous_seek_version TEXT;
    v_previous_count INT;

    -- Dynamic SQL for batch query only
    v_batch_query TEXT;
    v_batch_query_strict TEXT;
    v_delete_marker_peek_query TEXT;
    v_delete_marker_peek_query_strict TEXT;

BEGIN
    -- ========================================================================
    -- INITIALIZATION
    -- ========================================================================
    v_is_asc := lower(coalesce(sort_order, 'asc')) = 'asc';
    v_prefix := coalesce(prefix_param, '');
    v_start := CASE WHEN coalesce(next_token, '') <> '' THEN next_token ELSE coalesce(start_after, '') END;
    v_file_batch_size := LEAST(GREATEST(max_keys * 2, 100), 1000);
    v_next_seek_at := NULL;
    v_next_seek_version := '';

    -- COALESCE first: NULL NOT IN (...) evaluates to NULL (not TRUE), so a
    -- bare NOT IN check silently leaves an explicit NULL argument unreset.
    noncurrent_versions := COALESCE(noncurrent_versions, 'exclude');
    delete_markers := COALESCE(delete_markers, 'exclude');
    IF noncurrent_versions NOT IN ('exclude', 'only', 'include') THEN
        noncurrent_versions := 'exclude';
    END IF;
    IF delete_markers NOT IN ('exclude', 'only', 'include') THEN
        delete_markers := 'exclude';
    END IF;

    v_multi_row := noncurrent_versions IN ('only', 'include');
    v_name_order := CASE WHEN v_is_asc THEN 'ASC' ELSE 'DESC' END;

    v_version_filter := '';
    IF noncurrent_versions = 'exclude' THEN
        v_version_filter := v_version_filter || ' AND o.archived_at IS NULL';
    ELSIF noncurrent_versions = 'only' THEN
        v_version_filter := v_version_filter || ' AND o.archived_at IS NOT NULL';
    END IF;
    IF delete_markers = 'exclude' THEN
        v_version_filter := v_version_filter || ' AND NOT o.is_delete_marker';
    ELSIF delete_markers = 'only' THEN
        v_version_filter := v_version_filter || ' AND o.is_delete_marker';
    END IF;

    -- Calculate upper bound for prefix filtering (bytewise, using COLLATE "C")
    IF v_prefix = '' THEN
        v_upper_bound := NULL;
    ELSE
        v_upper_bound := left(v_prefix, -1) || chr(ascii(right(v_prefix, 1)) + 1);
    END IF;

    -- Keep caller-provided cursors inside the requested prefix range.
    IF v_start <> '' AND v_upper_bound IS NOT NULL THEN
        IF v_is_asc THEN
            IF v_start COLLATE "C" < v_prefix COLLATE "C" THEN
                v_start := '';
            ELSIF v_start COLLATE "C" >= v_upper_bound COLLATE "C" THEN
                RETURN;
            END IF;
        ELSE
            IF v_start COLLATE "C" < v_prefix COLLATE "C" THEN
                RETURN;
            ELSIF v_start COLLATE "C" >= v_upper_bound COLLATE "C" THEN
                v_start := '';
            END IF;
        END IF;
    END IF;

    v_start_relative := substring(v_start FROM length(v_prefix) + 1);

    -- Direction affects only the indexed name range and its ordering. Cursor
    -- state transitions and within-key version ordering stay shared.
    IF v_is_asc THEN
        v_exact_range_predicate := 'TRUE';
        v_strict_range_predicate := 'o.name COLLATE "C" > $2';
        v_inclusive_range_predicate := 'o.name COLLATE "C" >= $2';
        IF v_upper_bound IS NOT NULL THEN
            v_exact_range_predicate := 'o.name COLLATE "C" < $3';
            v_strict_range_predicate := v_strict_range_predicate || ' AND o.name COLLATE "C" < $3';
            v_inclusive_range_predicate := v_inclusive_range_predicate || ' AND o.name COLLATE "C" < $3';
        END IF;
    ELSE
        v_exact_range_predicate := 'TRUE';
        v_strict_range_predicate := 'o.name COLLATE "C" < $2';
        v_inclusive_range_predicate := 'o.name COLLATE "C" < $2';
        IF v_prefix <> '' THEN
            v_exact_range_predicate := 'o.name COLLATE "C" >= $3';
            v_strict_range_predicate := v_strict_range_predicate || ' AND o.name COLLATE "C" >= $3';
            v_inclusive_range_predicate := v_inclusive_range_predicate || ' AND o.name COLLATE "C" >= $3';
        END IF;
    END IF;

    -- Build batch query (dynamic SQL - called infrequently, amortized over many rows)
    -- The multi-row order matches the externally serialized cursor exactly:
    -- archived_at at millisecond precision, then version as the final tiebreak.
    --
    -- When v_multi_row, the seek is a keyset tuple comparison ("name > $2 OR
    -- (name = $2 AND tiebreak)") - Postgres won't split that OR into indexable
    -- form (confirmed even with fully literal values), so as one WHERE clause
    -- it forces a full bucket scan filtered row-by-row. Splitting it into two
    -- independently-indexable branches (exact name match with the tiebreak
    -- filter, vs. strictly-past names) combined with UNION ALL lets each
    -- branch keep name as a real index condition; the outer ORDER BY/LIMIT
    -- re-merges them into the same page the single query used to produce.
    IF v_multi_row THEN
        v_batch_query := format(
            $sql$
            SELECT *
            FROM (
                (
                    SELECT o.name, o.id, o.updated_at, o.created_at,
                           o.last_accessed_at, o.metadata, o.version,
                           o.archived_at, o.is_delete_marker, o.is_versioned
                    FROM storage.objects o
                    WHERE o.bucket_id = $1
                      AND o.name COLLATE "C" = $2
                      AND %s
                      AND NOT $7::boolean
                      AND (
                          $5::timestamptz IS NULL
                          OR COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) < $5
                          OR (
                              COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) = $5
                              AND COALESCE(o.version, '') > $6
                          )
                      )
                      %s
                    ORDER BY
                        COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) DESC,
                        COALESCE(o.version, '') ASC
                    LIMIT $4
                )
                UNION ALL
                (
                    SELECT o.name, o.id, o.updated_at, o.created_at,
                           o.last_accessed_at, o.metadata, o.version,
                           o.archived_at, o.is_delete_marker, o.is_versioned
                    FROM storage.objects o
                    WHERE o.bucket_id = $1
                      AND %s
                      %s
                    ORDER BY
                        o.name COLLATE "C" %s,
                        COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) DESC,
                        COALESCE(o.version, '') ASC
                    LIMIT $4
                )
            ) sub
            ORDER BY
                sub.name COLLATE "C" %s,
                COALESCE(date_trunc('milliseconds', sub.archived_at), 'infinity'::timestamptz) DESC,
                COALESCE(sub.version, '') ASC
            LIMIT $4
            $sql$,
            v_exact_range_predicate,
            v_version_filter,
            v_strict_range_predicate,
            v_version_filter,
            v_name_order,
            v_name_order
        );
    ELSE
        v_batch_query := format(
            $sql$
            SELECT o.name, o.id, o.updated_at, o.created_at,
                   o.last_accessed_at, o.metadata, o.version,
                   o.archived_at, o.is_delete_marker, o.is_versioned
            FROM storage.objects o
            WHERE o.bucket_id = $1
              AND %s
              %s
            ORDER BY o.name COLLATE "C" %s, o.archived_at DESC
            LIMIT $4
            $sql$,
            v_inclusive_range_predicate,
            v_version_filter,
            v_name_order
        );

        -- Strict counterpart of the query above: used once the single-row
        -- ASC batch advance (below) has left v_next_seek pointing at the
        -- last row already emitted, so an inclusive predicate would
        -- re-match it forever. Only single-row mode ever sets strict mode,
        -- so this variant is never needed when v_multi_row.
        v_batch_query_strict := format(
            $sql$
            SELECT o.name, o.id, o.updated_at, o.created_at,
                   o.last_accessed_at, o.metadata, o.version,
                   o.archived_at, o.is_delete_marker, o.is_versioned
            FROM storage.objects o
            WHERE o.bucket_id = $1
              AND %s
              %s
            ORDER BY o.name COLLATE "C" %s, o.archived_at DESC
            LIMIT $4
            $sql$,
            v_strict_range_predicate,
            v_version_filter,
            v_name_order
        );
    END IF;

    -- The static peek predicates cannot use the partial delete-marker index
    -- once PL/pgSQL switches to a generic plan because whether
    -- is_delete_marker is required remains parameter-dependent. Reuse the
    -- already-specialized batch query with a one-row limit for this sparse
    -- filter so the plan sees a literal `o.is_delete_marker` predicate.
    IF delete_markers = 'only' THEN
        v_delete_marker_peek_query :=
            'SELECT marker_page.name FROM (' || v_batch_query || ') marker_page LIMIT 1';
        IF NOT v_multi_row THEN
            v_delete_marker_peek_query_strict :=
                'SELECT marker_page.name FROM (' || v_batch_query_strict || ') marker_page LIMIT 1';
        END IF;
    END IF;

    -- ========================================================================
    -- SEEK INITIALIZATION: Determine starting position
    -- ========================================================================
    IF v_start = '' THEN
        IF v_is_asc THEN
            v_next_seek := v_prefix;
        ELSE
            -- DESC without cursor performs one specialized initial seek so
            -- partial current-version and delete-marker indexes remain available.
            EXECUTE format(
                'SELECT o.name FROM storage.objects o WHERE o.bucket_id = $1%s%s ORDER BY o.name COLLATE "C" DESC LIMIT 1',
                CASE WHEN v_upper_bound IS NOT NULL
                    THEN ' AND o.name COLLATE "C" >= $2 AND o.name COLLATE "C" < $3'
                    ELSE ''
                END,
                v_version_filter
            )
            INTO v_next_seek
            USING _bucket_id, v_prefix, v_upper_bound;

            IF v_next_seek IS NOT NULL THEN
                v_next_seek := v_next_seek || delimiter_param;
            ELSE
                RETURN;
            END IF;
        END IF;
    ELSE
        -- Folder continuation tokens retain their trailing delimiter. A
        -- delimiter-less startAfter is always a literal key boundary.
        v_cursor_is_folder := delimiter_param <> ''
            AND v_start_relative <> ''
            AND right(v_start_relative, length(delimiter_param)) = delimiter_param;

        IF v_cursor_is_folder THEN
            v_next_seek := CASE
                WHEN right(v_start, length(delimiter_param)) = delimiter_param
                    THEN v_start
                ELSE v_start || delimiter_param
            END;
            IF v_is_asc THEN
                v_next_seek := left(v_next_seek, -1)
                    || chr(ascii(right(v_next_seek, 1)) + 1);
            END IF;
            v_next_seek_strict := NOT v_is_asc;
        ELSE
            -- leaf object: when v_multi_row, stay on v_start with the
            -- caller-supplied tiebreak so a page boundary mid-key resumes
            -- that key's remaining rows instead of skipping them. Truncate
            -- to milliseconds like every other v_next_seek_at assignment -
            -- harmless today since object.ts's cursor always round-trips
            -- through JS Date first, but this shouldn't rely on that.
            IF v_multi_row THEN
                v_next_seek := v_start;
                v_next_seek_at := date_trunc('milliseconds', next_token_archived_at);
                v_next_seek_version := coalesce(next_token_version, '');
                v_next_seek_strict := coalesce(next_token, '') = '';
            ELSIF v_is_asc THEN
                v_next_seek := v_start;
                v_next_seek_strict := true;
            ELSE
                v_next_seek := v_start;
            END IF;
        END IF;
    END IF;

    -- ========================================================================
    -- MAIN LOOP: Hybrid peek-then-batch algorithm
    -- Uses STATIC SQL for peek (hot path) and DYNAMIC SQL for batch
    -- ========================================================================
    LOOP
        EXIT WHEN v_count >= max_keys;

        v_previous_seek := v_next_seek;
        v_previous_seek_at := v_next_seek_at;
        v_previous_seek_version := v_next_seek_version;
        v_previous_count := v_count;

        -- STEP 1: PEEK using STATIC SQL (plan cached, very fast)
        -- v_multi_row is branched here (rather than folded into the WHERE
        -- clause as a bound parameter) so each concrete query keeps an
        -- unconditional seek predicate - once PL/pgSQL switches to its
        -- cached generic plan (after 5 calls), a parameter-gated
        -- "(NOT v_multi_row AND name >= $x) OR (v_multi_row AND ...)"
        -- predicate stops the planner from using name as an index
        -- condition at all, degrading every subsequent peek to a full
        -- index scan filtered row-by-row instead of a bounded range scan.
        -- v_multi_row's seek predicate is a keyset tuple comparison
        -- ("name > x OR (name = x AND tiebreak)") - Postgres does not
        -- split this OR into indexable form even with fully literal
        -- values, so it falls back to a full scan filtered row-by-row.
        -- Splitting it into two independently-indexable branches (exact
        -- name match with the tiebreak filter, vs. strictly-past name)
        -- combined with UNION ALL lets each branch keep name as a real
        -- index condition; the outer ORDER BY/LIMIT picks whichever of
        -- the (at most 2) rows sorts first.
        IF delete_markers = 'only' THEN
            EXECUTE CASE WHEN v_next_seek_strict AND NOT v_multi_row
                THEN v_delete_marker_peek_query_strict
                ELSE v_delete_marker_peek_query
            END
                INTO v_peek_name
                USING _bucket_id, v_next_seek,
                    CASE WHEN v_is_asc THEN COALESCE(v_upper_bound, v_prefix) ELSE v_prefix END,
                    1, v_next_seek_at, v_next_seek_version, v_next_seek_strict;
        ELSIF v_multi_row THEN
            IF v_is_asc THEN
                IF v_upper_bound IS NOT NULL THEN
                    SELECT sub.name INTO v_peek_name FROM (
                        (SELECT o.name FROM storage.objects o
                         WHERE o.bucket_id = _bucket_id AND o.name COLLATE "C" = v_next_seek
                           AND o.name COLLATE "C" < v_upper_bound
                           AND NOT v_next_seek_strict
                           AND (v_next_seek_at IS NULL
                                OR COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) < v_next_seek_at
                                OR (COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) = v_next_seek_at
                                    AND COALESCE(o.version, '') > v_next_seek_version))
                           AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
                           AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
                           AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                           AND (delete_markers != 'only' OR o.is_delete_marker)
                         ORDER BY COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) DESC, COALESCE(o.version, '') ASC LIMIT 1)
                        UNION ALL
                        (SELECT o.name FROM storage.objects o
                         WHERE o.bucket_id = _bucket_id AND o.name COLLATE "C" > v_next_seek AND o.name COLLATE "C" < v_upper_bound
                           AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
                           AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
                           AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                           AND (delete_markers != 'only' OR o.is_delete_marker)
                         ORDER BY o.name COLLATE "C" ASC LIMIT 1)
                    ) sub ORDER BY sub.name COLLATE "C" ASC LIMIT 1;
                ELSE
                    SELECT sub.name INTO v_peek_name FROM (
                        (SELECT o.name FROM storage.objects o
                         WHERE o.bucket_id = _bucket_id AND o.name COLLATE "C" = v_next_seek
                           AND NOT v_next_seek_strict
                           AND (v_next_seek_at IS NULL
                                OR COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) < v_next_seek_at
                                OR (COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) = v_next_seek_at
                                    AND COALESCE(o.version, '') > v_next_seek_version))
                           AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
                           AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
                           AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                           AND (delete_markers != 'only' OR o.is_delete_marker)
                         ORDER BY COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) DESC, COALESCE(o.version, '') ASC LIMIT 1)
                        UNION ALL
                        (SELECT o.name FROM storage.objects o
                         WHERE o.bucket_id = _bucket_id AND o.name COLLATE "C" > v_next_seek
                           AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
                           AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
                           AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                           AND (delete_markers != 'only' OR o.is_delete_marker)
                         ORDER BY o.name COLLATE "C" ASC LIMIT 1)
                    ) sub ORDER BY sub.name COLLATE "C" ASC LIMIT 1;
                END IF;
            ELSE
                IF v_upper_bound IS NOT NULL THEN
                    SELECT sub.name INTO v_peek_name FROM (
                        (SELECT o.name FROM storage.objects o
                         WHERE o.bucket_id = _bucket_id AND o.name COLLATE "C" = v_next_seek
                           AND o.name COLLATE "C" >= v_prefix
                           AND NOT v_next_seek_strict
                           AND (v_next_seek_at IS NULL
                                OR COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) < v_next_seek_at
                                OR (COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) = v_next_seek_at
                                    AND COALESCE(o.version, '') > v_next_seek_version))
                           AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
                           AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
                           AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                           AND (delete_markers != 'only' OR o.is_delete_marker)
                         ORDER BY COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) DESC, COALESCE(o.version, '') ASC LIMIT 1)
                        UNION ALL
                        (SELECT o.name FROM storage.objects o
                         WHERE o.bucket_id = _bucket_id AND o.name COLLATE "C" < v_next_seek AND o.name COLLATE "C" >= v_prefix
                           AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
                           AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
                           AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                           AND (delete_markers != 'only' OR o.is_delete_marker)
                         ORDER BY o.name COLLATE "C" DESC LIMIT 1)
                    ) sub ORDER BY sub.name COLLATE "C" DESC LIMIT 1;
                ELSE
                    SELECT sub.name INTO v_peek_name FROM (
                        (SELECT o.name FROM storage.objects o
                         WHERE o.bucket_id = _bucket_id AND o.name COLLATE "C" = v_next_seek
                           AND NOT v_next_seek_strict
                           AND (v_next_seek_at IS NULL
                                OR COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) < v_next_seek_at
                                OR (COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) = v_next_seek_at
                                    AND COALESCE(o.version, '') > v_next_seek_version))
                           AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
                           AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
                           AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                           AND (delete_markers != 'only' OR o.is_delete_marker)
                         ORDER BY COALESCE(date_trunc('milliseconds', o.archived_at), 'infinity'::timestamptz) DESC, COALESCE(o.version, '') ASC LIMIT 1)
                        UNION ALL
                        (SELECT o.name FROM storage.objects o
                         WHERE o.bucket_id = _bucket_id AND o.name COLLATE "C" < v_next_seek
                           AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
                           AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
                           AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                           AND (delete_markers != 'only' OR o.is_delete_marker)
                         ORDER BY o.name COLLATE "C" DESC LIMIT 1)
                    ) sub ORDER BY sub.name COLLATE "C" DESC LIMIT 1;
                END IF;
            END IF;
        ELSE
            -- Single-row mode is always noncurrent_versions='exclude'. Keep
            -- this predicate literal so generic plans use the current index.
            IF v_is_asc THEN
                IF v_next_seek_strict AND v_upper_bound IS NOT NULL THEN
                    SELECT o.name INTO v_peek_name FROM storage.objects o
                    WHERE o.bucket_id = _bucket_id
                      AND o.name COLLATE "C" > v_next_seek
                      AND o.name COLLATE "C" < v_upper_bound
                      AND o.archived_at IS NULL
                      AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                      AND (delete_markers != 'only' OR o.is_delete_marker)
                    ORDER BY o.name COLLATE "C" ASC LIMIT 1;
                ELSIF v_next_seek_strict THEN
                    SELECT o.name INTO v_peek_name FROM storage.objects o
                    WHERE o.bucket_id = _bucket_id
                      AND o.name COLLATE "C" > v_next_seek
                      AND o.archived_at IS NULL
                      AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                      AND (delete_markers != 'only' OR o.is_delete_marker)
                    ORDER BY o.name COLLATE "C" ASC LIMIT 1;
                ELSIF v_upper_bound IS NOT NULL THEN
                    SELECT o.name INTO v_peek_name FROM storage.objects o
                    WHERE o.bucket_id = _bucket_id
                      AND o.name COLLATE "C" >= v_next_seek
                      AND o.name COLLATE "C" < v_upper_bound
                      AND o.archived_at IS NULL
                      AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                      AND (delete_markers != 'only' OR o.is_delete_marker)
                    ORDER BY o.name COLLATE "C" ASC LIMIT 1;
                ELSE
                    SELECT o.name INTO v_peek_name FROM storage.objects o
                    WHERE o.bucket_id = _bucket_id
                      AND o.name COLLATE "C" >= v_next_seek
                      AND o.archived_at IS NULL
                      AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                      AND (delete_markers != 'only' OR o.is_delete_marker)
                    ORDER BY o.name COLLATE "C" ASC LIMIT 1;
                END IF;
            ELSE
                IF v_upper_bound IS NOT NULL THEN
                    SELECT o.name INTO v_peek_name FROM storage.objects o
                    WHERE o.bucket_id = _bucket_id
                      AND o.name COLLATE "C" < v_next_seek
                      AND o.name COLLATE "C" >= v_prefix
                      AND o.archived_at IS NULL
                      AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                      AND (delete_markers != 'only' OR o.is_delete_marker)
                    ORDER BY o.name COLLATE "C" DESC LIMIT 1;
                ELSE
                    SELECT o.name INTO v_peek_name FROM storage.objects o
                    WHERE o.bucket_id = _bucket_id
                      AND o.name COLLATE "C" < v_next_seek
                      AND o.archived_at IS NULL
                      AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                      AND (delete_markers != 'only' OR o.is_delete_marker)
                    ORDER BY o.name COLLATE "C" DESC LIMIT 1;
                END IF;
            END IF;
        END IF;

        EXIT WHEN v_peek_name IS NULL;

        -- STEP 2: Check if this is a FOLDER or FILE
        v_common_prefix := storage.get_common_prefix(v_peek_name, v_prefix, delimiter_param);

        IF v_common_prefix IS NOT NULL THEN
            -- FOLDER: Emit and skip to next folder (no heap access needed)
            name := v_common_prefix;
            id := NULL;
            updated_at := NULL;
            created_at := NULL;
            last_accessed_at := NULL;
            metadata := NULL;
            version := NULL;
            archived_at := NULL;
            is_delete_marker := NULL;
            is_versioned := NULL;
            RETURN NEXT;
            v_count := v_count + 1;

            -- Advance seek past the folder range
            IF v_is_asc THEN
                v_next_seek := left(v_common_prefix, -1)
                    || chr(ascii(right(v_common_prefix, 1)) + 1);
            ELSE
                v_next_seek := v_common_prefix;
            END IF;
            v_next_seek_at := NULL;
            v_next_seek_version := '';
            v_next_seek_strict := NOT v_is_asc;
        ELSE
            -- FILE: Batch fetch using DYNAMIC SQL (overhead amortized over many rows)
            -- For ASC: upper_bound is the exclusive upper limit (< condition)
            -- For DESC: prefix is the inclusive lower limit (>= condition)
            FOR v_current IN EXECUTE CASE WHEN v_next_seek_strict AND NOT v_multi_row THEN v_batch_query_strict ELSE v_batch_query END
                USING _bucket_id, v_next_seek,
                CASE WHEN v_is_asc THEN COALESCE(v_upper_bound, v_prefix) ELSE v_prefix END, v_file_batch_size, v_next_seek_at, v_next_seek_version,
                v_next_seek_strict
            LOOP
                v_common_prefix := storage.get_common_prefix(v_current.name, v_prefix, delimiter_param);

                IF v_common_prefix IS NOT NULL THEN
                    -- Hit a folder: exit batch, let peek handle it. Reset
                    -- strict mode too it may have been set by an earlier
                    -- row in this same batch (see the single-row ASC advance
                    -- below), and v_next_seek here is the folder-triggering
                    -- row's own name, which the next peek must find inclusively.
                    v_next_seek := CASE
                        WHEN v_is_asc THEN v_current.name
                        ELSE v_current.name || delimiter_param
                    END;
                    v_next_seek_at := NULL;
                    v_next_seek_version := '';
                    v_next_seek_strict := false;
                    EXIT;
                END IF;

                -- Emit file
                name := v_current.name;
                id := v_current.id;
                updated_at := v_current.updated_at;
                created_at := v_current.created_at;
                last_accessed_at := v_current.last_accessed_at;
                metadata := v_current.metadata;
                version := v_current.version;
                archived_at := v_current.archived_at;
                is_delete_marker := v_current.is_delete_marker;
                is_versioned := v_current.is_versioned;
                RETURN NEXT;
                v_count := v_count + 1;

                -- when v_multi_row, stay on this name and record its
                -- archived_at as the new tiebreak so remaining rows for the
                -- same key are picked up before moving to the next name
                IF v_multi_row THEN
                    v_next_seek := v_current.name;
                    v_next_seek_at := COALESCE(date_trunc('milliseconds', v_current.archived_at), 'infinity'::timestamptz);
                    v_next_seek_version := COALESCE(v_current.version, '');
                    v_next_seek_strict := false;
                ELSIF v_is_asc THEN
                    -- Appending the delimiter as a fake lexical successor
                    -- would skip a real key like `name || '!'` (or any
                    -- character sorting below the delimiter), which sorts
                    -- between `name` and `name || delimiter`. Track the real
                    -- name and mark the next comparison strict instead.
                    v_next_seek := v_current.name;
                    v_next_seek_strict := true;
                ELSE
                    v_next_seek := v_current.name;
                END IF;

                EXIT WHEN v_count >= max_keys;
            END LOOP;
        END IF;

        IF v_count = v_previous_count
           AND v_next_seek IS NOT DISTINCT FROM v_previous_seek
           AND v_next_seek_at IS NOT DISTINCT FROM v_previous_seek_at
           AND v_next_seek_version IS NOT DISTINCT FROM v_previous_seek_version THEN
            RAISE EXCEPTION 'storage.list_objects_with_delimiter made no progress at seek (%, %, %)',
                v_next_seek, v_next_seek_at, v_next_seek_version;
        END IF;
    END LOOP;
END;
$_$;


ALTER FUNCTION storage.list_objects_with_delimiter(_bucket_id text, prefix_param text, delimiter_param text, max_keys integer, start_after text, next_token text, sort_order text, noncurrent_versions text, delete_markers text, next_token_archived_at timestamp with time zone, next_token_version text) OWNER TO supabase_storage_admin;

--
-- Name: operation(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.operation() RETURNS text
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
    RETURN current_setting('storage.operation', true);
END;
$$;


ALTER FUNCTION storage.operation() OWNER TO supabase_storage_admin;

--
-- Name: protect_bucket_control_columns(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.protect_bucket_control_columns() RETURNS trigger
    LANGUAGE plpgsql
    SET search_path TO 'pg_catalog'
    AS $$
DECLARE
  configuration_changed boolean;
BEGIN
  IF TG_OP = 'INSERT' THEN
    IF NEW.lifecycle_configuration IS NOT NULL
       OR NEW.lifecycle_configuration_generation IS NOT NULL THEN
      IF NOT pg_has_role(current_user, TG_ARGV[0], 'MEMBER') THEN
        RAISE EXCEPTION 'only members of the configured storage service role may insert lifecycle policy state'
          USING ERRCODE = '42501',
                HINT = format(
                  'Insert with both lifecycle columns NULL and configure lifecycle through the Storage API afterward, or insert as a member of %I.',
                  TG_ARGV[0]
                );
      END IF;
    END IF;

    RETURN NEW;
  END IF;

  configuration_changed =
    OLD.lifecycle_configuration IS DISTINCT FROM NEW.lifecycle_configuration
    OR OLD.lifecycle_configuration_generation IS DISTINCT FROM NEW.lifecycle_configuration_generation;

  IF NOT configuration_changed THEN
    RETURN NEW;
  END IF;

  IF NEW.type IS DISTINCT FROM 'STANDARD' THEN
    RAISE EXCEPTION 'bucket versioning and lifecycle controls require a Standard bucket'
      USING ERRCODE = '0A000';
  END IF;

  IF NEW.lifecycle_configuration IS NULL
     AND NEW.lifecycle_configuration_generation IS NULL THEN
    RETURN NEW;
  END IF;

  IF NEW.lifecycle_configuration IS NULL
     OR NEW.lifecycle_configuration_generation IS NULL
     OR OLD.lifecycle_configuration IS NOT DISTINCT FROM NEW.lifecycle_configuration
     OR OLD.lifecycle_configuration_generation IS NOT DISTINCT FROM NEW.lifecycle_configuration_generation THEN
    RAISE EXCEPTION 'a changed lifecycle policy requires a new non-null generation'
      USING ERRCODE = '22023';
  END IF;

  RETURN NEW;
END;
$$;


ALTER FUNCTION storage.protect_bucket_control_columns() OWNER TO supabase_storage_admin;

--
-- Name: protect_delete(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.protect_delete() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Check if storage.allow_delete_query is set to 'true'
    IF COALESCE(current_setting('storage.allow_delete_query', true), 'false') != 'true' THEN
        RAISE EXCEPTION 'Direct deletion from storage tables is not allowed. Use the Storage API instead.'
            USING HINT = 'This prevents accidental data loss from orphaned objects.',
                  ERRCODE = '42501';
    END IF;
    RETURN NULL;
END;
$$;


ALTER FUNCTION storage.protect_delete() OWNER TO supabase_storage_admin;

--
-- Name: search(text, text, integer, integer, integer, text, text, text, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.search(prefix text, bucketname text, limits integer DEFAULT 100, levels integer DEFAULT 1, offsets integer DEFAULT 0, search text DEFAULT ''::text, sortcolumn text DEFAULT 'name'::text, sortorder text DEFAULT 'asc'::text, noncurrent_versions text DEFAULT 'exclude'::text, delete_markers text DEFAULT 'exclude'::text) RETURNS TABLE(name text, id uuid, updated_at timestamp with time zone, created_at timestamp with time zone, last_accessed_at timestamp with time zone, metadata jsonb, version text, archived_at timestamp with time zone, is_delete_marker boolean, is_versioned boolean)
    LANGUAGE plpgsql STABLE
    AS $_$
DECLARE
    v_peek_name TEXT;
    v_current RECORD;
    v_common_prefix TEXT;
    v_delimiter CONSTANT TEXT := '/';

    -- Configuration
    v_limit INT;
    v_prefix TEXT;
    v_prefix_lower TEXT;
    v_prefix_len INT;
    v_prefix_start INT;
    v_combined_levels INT;
    v_is_asc BOOLEAN;
    v_order_by TEXT;
    v_sort_order TEXT;
    v_upper_bound TEXT;
    v_file_batch_size INT;
    v_version_filter TEXT;
    v_multi_row BOOLEAN;

    -- Dynamic SQL for batch query only
    v_batch_query TEXT;
    v_delete_marker_peek_query TEXT;
    v_delete_marker_peek_query_strict TEXT;

    -- Seek state
    v_next_seek TEXT;
    v_next_seek_at TIMESTAMPTZ;
    v_next_seek_version TEXT;
    v_next_seek_strict BOOLEAN := false;
    v_count INT := 0;
    v_skipped INT := 0;
    v_previous_seek TEXT;
    v_previous_seek_at TIMESTAMPTZ;
    v_previous_seek_version TEXT;
    v_previous_count INT;
    v_previous_skipped INT;
BEGIN
    -- ========================================================================
    -- INITIALIZATION
    -- ========================================================================
    v_limit := LEAST(coalesce(limits, 100), 1500);
    v_prefix := coalesce(prefix, '') || coalesce(search, '');
    v_prefix_lower := lower(v_prefix);
    v_prefix_len := length(coalesce(prefix, ''));
    v_prefix_start := coalesce(array_length(string_to_array(coalesce(prefix, ''), v_delimiter), 1), 1);
    v_combined_levels := coalesce(array_length(string_to_array(v_prefix, v_delimiter), 1), 1);
    v_is_asc := lower(coalesce(sortorder, 'asc')) = 'asc';
    v_file_batch_size := LEAST(GREATEST(v_limit * 2, 100), 1000);
    v_next_seek_at := NULL;
    v_next_seek_version := '';

    -- COALESCE first: NULL NOT IN (...) evaluates to NULL (not TRUE), so a
    -- bare NOT IN check silently leaves an explicit NULL argument unreset.
    noncurrent_versions := COALESCE(noncurrent_versions, 'exclude');
    delete_markers := COALESCE(delete_markers, 'exclude');
    IF noncurrent_versions NOT IN ('exclude', 'only', 'include') THEN
        noncurrent_versions := 'exclude';
    END IF;
    IF delete_markers NOT IN ('exclude', 'only', 'include') THEN
        delete_markers := 'exclude';
    END IF;

    v_multi_row := noncurrent_versions IN ('only', 'include');

    v_version_filter := '';
    IF noncurrent_versions = 'exclude' THEN
        v_version_filter := v_version_filter || ' AND o.archived_at IS NULL';
    ELSIF noncurrent_versions = 'only' THEN
        v_version_filter := v_version_filter || ' AND o.archived_at IS NOT NULL';
    END IF;
    IF delete_markers = 'exclude' THEN
        v_version_filter := v_version_filter || ' AND NOT o.is_delete_marker';
    ELSIF delete_markers = 'only' THEN
        v_version_filter := v_version_filter || ' AND o.is_delete_marker';
    END IF;

    -- Validate sort column
    CASE lower(coalesce(sortcolumn, 'name'))
        WHEN 'name' THEN v_order_by := 'name';
        WHEN 'updated_at' THEN v_order_by := 'updated_at';
        WHEN 'created_at' THEN v_order_by := 'created_at';
        WHEN 'last_accessed_at' THEN v_order_by := 'last_accessed_at';
        ELSE v_order_by := 'name';
    END CASE;

    v_sort_order := CASE WHEN v_is_asc THEN 'asc' ELSE 'desc' END;

    -- ========================================================================
    -- NON-NAME SORTING: Use path_tokens approach
    -- ========================================================================
    IF v_order_by != 'name' THEN
        RETURN QUERY EXECUTE format(
            $sql$
            WITH folders AS (
                SELECT array_to_string(path_tokens[$1:$2], '/') AS folder
                FROM storage.objects
                WHERE objects.name ILIKE $3 || '%%'
                  AND bucket_id = $4
                  AND array_length(objects.path_tokens, 1) <> $2
                  AND ($7 != 'exclude' OR objects.archived_at IS NULL)
                  AND ($7 != 'only' OR objects.archived_at IS NOT NULL)
                  AND ($8 != 'exclude' OR NOT objects.is_delete_marker)
                  AND ($8 != 'only' OR objects.is_delete_marker)
                GROUP BY folder
                ORDER BY folder %s
            )
            (SELECT folder AS "name",
                   NULL::uuid AS id,
                   NULL::timestamptz AS updated_at,
                   NULL::timestamptz AS created_at,
                   NULL::timestamptz AS last_accessed_at,
                   NULL::jsonb AS metadata,
                   NULL::text AS version,
                   NULL::timestamptz AS archived_at,
                   NULL::boolean AS is_delete_marker,
                   NULL::boolean AS is_versioned FROM folders)
            UNION ALL
            (SELECT array_to_string(path_tokens[$1:$2], '/') AS "name",
                   id, updated_at, created_at, last_accessed_at, metadata,
                   version, archived_at, is_delete_marker, is_versioned
             FROM storage.objects
             WHERE objects.name ILIKE $3 || '%%'
               AND bucket_id = $4
               AND array_length(objects.path_tokens, 1) = $2
               AND ($7 != 'exclude' OR objects.archived_at IS NULL)
               AND ($7 != 'only' OR objects.archived_at IS NOT NULL)
               AND ($8 != 'exclude' OR NOT objects.is_delete_marker)
               AND ($8 != 'only' OR objects.is_delete_marker)
             -- name, then version, as tiebreaks so two versions of the same
             -- key tying on the sort column still sort deterministically
             ORDER BY %I %s, name COLLATE "C" %s, COALESCE(version, '') %s)
            LIMIT $5 OFFSET $6
            $sql$, v_sort_order, v_order_by, v_sort_order, v_sort_order, v_sort_order
        ) USING v_prefix_start, v_combined_levels, v_prefix, bucketname, v_limit, offsets, noncurrent_versions, delete_markers;
        RETURN;
    END IF;

    -- ========================================================================
    -- NAME SORTING: Hybrid skip-scan with batch optimization
    -- ========================================================================

    -- Calculate upper bound for prefix filtering
    IF v_prefix_lower = '' THEN
        v_upper_bound := NULL;
    ELSIF right(v_prefix_lower, 1) = v_delimiter THEN
        v_upper_bound := left(v_prefix_lower, -1) || chr(ascii(v_delimiter) + 1);
    ELSE
        v_upper_bound := left(v_prefix_lower, -1) || chr(ascii(right(v_prefix_lower, 1)) + 1);
    END IF;

    -- Build a resume-safe batch query. The exact-name branch returns remaining
    -- versions after the current (archived_at, version) boundary; the strict
    -- name branch returns subsequent keys. UNION ALL keeps both predicates
    -- independently indexable.
    IF v_is_asc THEN
        IF v_upper_bound IS NOT NULL THEN
            v_batch_query := 'SELECT * FROM (' ||
                '(SELECT o.name, o.id, o.updated_at, o.created_at, o.last_accessed_at, o.metadata, o.version, o.archived_at, o.is_delete_marker, o.is_versioned FROM storage.objects o ' ||
                'WHERE o.bucket_id = $1 AND lower(o.name) COLLATE "C" = $2 AND ($5::timestamptz IS NULL OR COALESCE(o.archived_at, ''infinity''::timestamptz) < $5 OR (COALESCE(o.archived_at, ''infinity''::timestamptz) = $5 AND COALESCE(o.version, '''') > $6))' ||
                v_version_filter || ' ORDER BY COALESCE(o.archived_at, ''infinity''::timestamptz) DESC, COALESCE(o.version, '''') ASC LIMIT $4) UNION ALL ' ||
                '(SELECT o.name, o.id, o.updated_at, o.created_at, o.last_accessed_at, o.metadata, o.version, o.archived_at, o.is_delete_marker, o.is_versioned FROM storage.objects o ' ||
                'WHERE o.bucket_id = $1 AND lower(o.name) COLLATE "C" > $2 AND lower(o.name) COLLATE "C" < $3' || v_version_filter ||
                ' ORDER BY lower(o.name) COLLATE "C" ASC, COALESCE(o.archived_at, ''infinity''::timestamptz) DESC, COALESCE(o.version, '''') ASC LIMIT $4)' ||
                ') sub ORDER BY lower(sub.name) COLLATE "C" ASC, COALESCE(sub.archived_at, ''infinity''::timestamptz) DESC, COALESCE(sub.version, '''') ASC LIMIT $4';
        ELSE
            v_batch_query := 'SELECT * FROM (' ||
                '(SELECT o.name, o.id, o.updated_at, o.created_at, o.last_accessed_at, o.metadata, o.version, o.archived_at, o.is_delete_marker, o.is_versioned FROM storage.objects o ' ||
                'WHERE o.bucket_id = $1 AND lower(o.name) COLLATE "C" = $2 AND ($5::timestamptz IS NULL OR COALESCE(o.archived_at, ''infinity''::timestamptz) < $5 OR (COALESCE(o.archived_at, ''infinity''::timestamptz) = $5 AND COALESCE(o.version, '''') > $6))' ||
                v_version_filter || ' ORDER BY COALESCE(o.archived_at, ''infinity''::timestamptz) DESC, COALESCE(o.version, '''') ASC LIMIT $4) UNION ALL ' ||
                '(SELECT o.name, o.id, o.updated_at, o.created_at, o.last_accessed_at, o.metadata, o.version, o.archived_at, o.is_delete_marker, o.is_versioned FROM storage.objects o ' ||
                'WHERE o.bucket_id = $1 AND lower(o.name) COLLATE "C" > $2' || v_version_filter ||
                ' ORDER BY lower(o.name) COLLATE "C" ASC, COALESCE(o.archived_at, ''infinity''::timestamptz) DESC, COALESCE(o.version, '''') ASC LIMIT $4)' ||
                ') sub ORDER BY lower(sub.name) COLLATE "C" ASC, COALESCE(sub.archived_at, ''infinity''::timestamptz) DESC, COALESCE(sub.version, '''') ASC LIMIT $4';
        END IF;
    ELSE
        IF v_upper_bound IS NOT NULL THEN
            v_batch_query := 'SELECT * FROM (' ||
                '(SELECT o.name, o.id, o.updated_at, o.created_at, o.last_accessed_at, o.metadata, o.version, o.archived_at, o.is_delete_marker, o.is_versioned FROM storage.objects o ' ||
                'WHERE o.bucket_id = $1 AND lower(o.name) COLLATE "C" = $2 AND ($5::timestamptz IS NULL OR COALESCE(o.archived_at, ''infinity''::timestamptz) < $5 OR (COALESCE(o.archived_at, ''infinity''::timestamptz) = $5 AND COALESCE(o.version, '''') > $6))' ||
                v_version_filter || ' ORDER BY COALESCE(o.archived_at, ''infinity''::timestamptz) DESC, COALESCE(o.version, '''') ASC LIMIT $4) UNION ALL ' ||
                '(SELECT o.name, o.id, o.updated_at, o.created_at, o.last_accessed_at, o.metadata, o.version, o.archived_at, o.is_delete_marker, o.is_versioned FROM storage.objects o ' ||
                'WHERE o.bucket_id = $1 AND lower(o.name) COLLATE "C" < $2 AND lower(o.name) COLLATE "C" >= $3' || v_version_filter ||
                ' ORDER BY lower(o.name) COLLATE "C" DESC, COALESCE(o.archived_at, ''infinity''::timestamptz) DESC, COALESCE(o.version, '''') ASC LIMIT $4)' ||
                ') sub ORDER BY lower(sub.name) COLLATE "C" DESC, COALESCE(sub.archived_at, ''infinity''::timestamptz) DESC, COALESCE(sub.version, '''') ASC LIMIT $4';
        ELSE
            v_batch_query := 'SELECT * FROM (' ||
                '(SELECT o.name, o.id, o.updated_at, o.created_at, o.last_accessed_at, o.metadata, o.version, o.archived_at, o.is_delete_marker, o.is_versioned FROM storage.objects o ' ||
                'WHERE o.bucket_id = $1 AND lower(o.name) COLLATE "C" = $2 AND ($5::timestamptz IS NULL OR COALESCE(o.archived_at, ''infinity''::timestamptz) < $5 OR (COALESCE(o.archived_at, ''infinity''::timestamptz) = $5 AND COALESCE(o.version, '''') > $6))' ||
                v_version_filter || ' ORDER BY COALESCE(o.archived_at, ''infinity''::timestamptz) DESC, COALESCE(o.version, '''') ASC LIMIT $4) UNION ALL ' ||
                '(SELECT o.name, o.id, o.updated_at, o.created_at, o.last_accessed_at, o.metadata, o.version, o.archived_at, o.is_delete_marker, o.is_versioned FROM storage.objects o ' ||
                'WHERE o.bucket_id = $1 AND lower(o.name) COLLATE "C" < $2' || v_version_filter ||
                ' ORDER BY lower(o.name) COLLATE "C" DESC, COALESCE(o.archived_at, ''infinity''::timestamptz) DESC, COALESCE(o.version, '''') ASC LIMIT $4)' ||
                ') sub ORDER BY lower(sub.name) COLLATE "C" DESC, COALESCE(sub.archived_at, ''infinity''::timestamptz) DESC, COALESCE(sub.version, '''') ASC LIMIT $4';
        END IF;
    END IF;

    -- Keep the delete-marker predicate literal so the cached generic
    -- plan can use idx_objects_delete_markers during the main-loop peek.
    IF delete_markers = 'only' THEN
        IF v_multi_row THEN
            v_delete_marker_peek_query :=
                'SELECT marker_page.name FROM (' || v_batch_query || ') marker_page LIMIT 1';
        ELSIF v_is_asc THEN
            -- Two separate literal query strings, not one gated by a bound
            -- boolean: folding "$n AND op1 OR NOT $n AND op2" into a single
            -- query defeats the generic plan's ability to push either
            -- comparison into the index. Branching in PL/pgSQL control flow
            -- instead keeps each query's index condition intact.
            v_delete_marker_peek_query :=
                'SELECT o.name FROM storage.objects o WHERE o.bucket_id = $1 ' ||
                'AND lower(o.name) COLLATE "C" >= $2' ||
                CASE WHEN v_upper_bound IS NOT NULL
                    THEN ' AND lower(o.name) COLLATE "C" < $3'
                    ELSE ''
                END ||
                v_version_filter ||
                ' ORDER BY lower(o.name) COLLATE "C" ASC LIMIT 1';
            -- Strict variant: used once the single-row ASC batch advance
            -- (below) has left v_next_seek pointing at the last row already
            -- emitted, so a plain >= would re-match it forever.
            v_delete_marker_peek_query_strict :=
                'SELECT o.name FROM storage.objects o WHERE o.bucket_id = $1 ' ||
                'AND lower(o.name) COLLATE "C" > $2' ||
                CASE WHEN v_upper_bound IS NOT NULL
                    THEN ' AND lower(o.name) COLLATE "C" < $3'
                    ELSE ''
                END ||
                v_version_filter ||
                ' ORDER BY lower(o.name) COLLATE "C" ASC LIMIT 1';
        ELSE
            v_delete_marker_peek_query :=
                'SELECT o.name FROM storage.objects o WHERE o.bucket_id = $1 ' ||
                'AND lower(o.name) COLLATE "C" < $2' ||
                CASE WHEN v_upper_bound IS NOT NULL
                    THEN ' AND lower(o.name) COLLATE "C" >= $3'
                    ELSE ''
                END ||
                v_version_filter ||
                ' ORDER BY lower(o.name) COLLATE "C" DESC LIMIT 1';
        END IF;
    END IF;

    -- Initialize seek position
    IF v_is_asc THEN
        v_next_seek := v_prefix_lower;
    ELSE
        -- DESC performs one specialized initial seek so partial current-version
        -- and delete-marker indexes remain available.
        EXECUTE format(
            'SELECT o.name FROM storage.objects o WHERE o.bucket_id = $1%s%s ORDER BY lower(o.name) COLLATE "C" DESC LIMIT 1',
            CASE WHEN v_upper_bound IS NOT NULL
                THEN ' AND lower(o.name) COLLATE "C" >= $2 AND lower(o.name) COLLATE "C" < $3'
                ELSE ''
            END,
            v_version_filter
        )
        INTO v_peek_name
        USING bucketname, v_prefix_lower, v_upper_bound;

        IF v_peek_name IS NOT NULL THEN
            v_next_seek := lower(v_peek_name) || v_delimiter;
        ELSE
            RETURN;
        END IF;
    END IF;

    -- ========================================================================
    -- MAIN LOOP: Hybrid peek-then-batch algorithm
    -- Uses STATIC SQL for peek (hot path) and DYNAMIC SQL for batch and
    -- the delete-marker-only path
    -- ========================================================================
    LOOP
        EXIT WHEN v_count >= v_limit;

        v_previous_seek := v_next_seek;
        v_previous_seek_at := v_next_seek_at;
        v_previous_seek_version := v_next_seek_version;
        v_previous_count := v_count;
        v_previous_skipped := v_skipped;

        -- STEP 1: PEEK
        v_peek_name := NULL;
        IF delete_markers = 'only' THEN
            EXECUTE CASE WHEN v_next_seek_strict
                THEN v_delete_marker_peek_query_strict
                ELSE v_delete_marker_peek_query
            END
                INTO v_peek_name
                USING bucketname, v_next_seek,
                    CASE WHEN v_is_asc THEN COALESCE(v_upper_bound, v_prefix_lower) ELSE v_prefix_lower END,
                    1, v_next_seek_at, v_next_seek_version;
        ELSIF v_multi_row AND v_next_seek_at IS NOT NULL THEN
            SELECT o.name INTO v_peek_name
            FROM storage.objects o
            WHERE o.bucket_id = bucketname
              AND lower(o.name) COLLATE "C" = v_next_seek
              AND (COALESCE(o.archived_at, 'infinity'::timestamptz) < v_next_seek_at
                   OR (COALESCE(o.archived_at, 'infinity'::timestamptz) = v_next_seek_at
                       AND COALESCE(o.version, '') > v_next_seek_version))
              AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
              AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
              AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
              AND (delete_markers != 'only' OR o.is_delete_marker)
            ORDER BY COALESCE(o.archived_at, 'infinity'::timestamptz) DESC,
                     COALESCE(o.version, '') ASC
            LIMIT 1;

            -- The current key is exhausted. Clear its version boundary and
            -- make the following ASC name peek strict. Appending '/' is not a
            -- valid lexical successor because keys ending in characters such
            -- as '!' sort between the exhausted name and name || '/'.
            IF v_peek_name IS NULL THEN
                IF v_is_asc THEN
                    v_next_seek_strict := true;
                END IF;
                v_next_seek_at := NULL;
                v_next_seek_version := '';
            END IF;
        END IF;

        -- Single-row mode is always noncurrent_versions='exclude'. Keep the
        -- current-row predicate literal so generic plans use the current index.
        IF delete_markers != 'only' AND v_peek_name IS NULL AND NOT v_multi_row THEN
            IF v_is_asc THEN
                IF v_next_seek_strict AND v_upper_bound IS NOT NULL THEN
                    SELECT o.name INTO v_peek_name FROM storage.objects o
                    WHERE o.bucket_id = bucketname AND lower(o.name) COLLATE "C" > v_next_seek AND lower(o.name) COLLATE "C" < v_upper_bound
                      AND o.archived_at IS NULL
                      AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                    ORDER BY lower(o.name) COLLATE "C" ASC LIMIT 1;
                ELSIF v_next_seek_strict THEN
                    SELECT o.name INTO v_peek_name FROM storage.objects o
                    WHERE o.bucket_id = bucketname AND lower(o.name) COLLATE "C" > v_next_seek
                      AND o.archived_at IS NULL
                      AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                    ORDER BY lower(o.name) COLLATE "C" ASC LIMIT 1;
                ELSIF v_upper_bound IS NOT NULL THEN
                    SELECT o.name INTO v_peek_name FROM storage.objects o
                    WHERE o.bucket_id = bucketname AND lower(o.name) COLLATE "C" >= v_next_seek AND lower(o.name) COLLATE "C" < v_upper_bound
                      AND o.archived_at IS NULL
                      AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                    ORDER BY lower(o.name) COLLATE "C" ASC LIMIT 1;
                ELSE
                    SELECT o.name INTO v_peek_name FROM storage.objects o
                    WHERE o.bucket_id = bucketname AND lower(o.name) COLLATE "C" >= v_next_seek
                      AND o.archived_at IS NULL
                      AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                    ORDER BY lower(o.name) COLLATE "C" ASC LIMIT 1;
                END IF;
            ELSIF v_upper_bound IS NOT NULL THEN
                SELECT o.name INTO v_peek_name FROM storage.objects o
                WHERE o.bucket_id = bucketname AND lower(o.name) COLLATE "C" < v_next_seek AND lower(o.name) COLLATE "C" >= v_prefix_lower
                  AND o.archived_at IS NULL
                  AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                ORDER BY lower(o.name) COLLATE "C" DESC LIMIT 1;
            ELSE
                SELECT o.name INTO v_peek_name FROM storage.objects o
                WHERE o.bucket_id = bucketname AND lower(o.name) COLLATE "C" < v_next_seek
                  AND o.archived_at IS NULL
                  AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                ORDER BY lower(o.name) COLLATE "C" DESC LIMIT 1;
            END IF;
        ELSIF delete_markers != 'only' AND v_peek_name IS NULL AND v_is_asc THEN
            IF v_next_seek_strict AND v_upper_bound IS NOT NULL THEN
                SELECT o.name INTO v_peek_name FROM storage.objects o
                WHERE o.bucket_id = bucketname AND lower(o.name) COLLATE "C" > v_next_seek AND lower(o.name) COLLATE "C" < v_upper_bound
                  AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
                  AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
                  AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                  AND (delete_markers != 'only' OR o.is_delete_marker)
                ORDER BY lower(o.name) COLLATE "C" ASC LIMIT 1;
            ELSIF v_next_seek_strict THEN
                SELECT o.name INTO v_peek_name FROM storage.objects o
                WHERE o.bucket_id = bucketname AND lower(o.name) COLLATE "C" > v_next_seek
                  AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
                  AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
                  AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                  AND (delete_markers != 'only' OR o.is_delete_marker)
                ORDER BY lower(o.name) COLLATE "C" ASC LIMIT 1;
            ELSIF v_upper_bound IS NOT NULL THEN
                SELECT o.name INTO v_peek_name FROM storage.objects o
                WHERE o.bucket_id = bucketname AND lower(o.name) COLLATE "C" >= v_next_seek AND lower(o.name) COLLATE "C" < v_upper_bound
                  AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
                  AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
                  AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                  AND (delete_markers != 'only' OR o.is_delete_marker)
                ORDER BY lower(o.name) COLLATE "C" ASC LIMIT 1;
            ELSE
                SELECT o.name INTO v_peek_name FROM storage.objects o
                WHERE o.bucket_id = bucketname AND lower(o.name) COLLATE "C" >= v_next_seek
                  AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
                  AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
                  AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                  AND (delete_markers != 'only' OR o.is_delete_marker)
                ORDER BY lower(o.name) COLLATE "C" ASC LIMIT 1;
            END IF;
        ELSIF delete_markers != 'only' AND v_peek_name IS NULL THEN
            IF v_upper_bound IS NOT NULL THEN
                SELECT o.name INTO v_peek_name FROM storage.objects o
                WHERE o.bucket_id = bucketname AND lower(o.name) COLLATE "C" < v_next_seek AND lower(o.name) COLLATE "C" >= v_prefix_lower
                  AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
                  AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
                  AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                  AND (delete_markers != 'only' OR o.is_delete_marker)
                ORDER BY lower(o.name) COLLATE "C" DESC LIMIT 1;
            ELSE
                SELECT o.name INTO v_peek_name FROM storage.objects o
                WHERE o.bucket_id = bucketname AND lower(o.name) COLLATE "C" < v_next_seek
                  AND (noncurrent_versions != 'exclude' OR o.archived_at IS NULL)
                  AND (noncurrent_versions != 'only' OR o.archived_at IS NOT NULL)
                  AND (delete_markers != 'exclude' OR NOT o.is_delete_marker)
                  AND (delete_markers != 'only' OR o.is_delete_marker)
                ORDER BY lower(o.name) COLLATE "C" DESC LIMIT 1;
            END IF;
        END IF;

        EXIT WHEN v_peek_name IS NULL;

        -- If the peek landed on a different key than we were tracking, any
        -- version boundary belongs to the OLD key and must not leak into the
        -- new one - e.g. the deleteMarkers='only' peek doesn't know or care
        -- whether it's continuing the same key or jumping to a new one, so
        -- it never clears these itself.
        IF lower(v_peek_name) IS DISTINCT FROM v_next_seek THEN
            v_next_seek_at := NULL;
            v_next_seek_version := '';
        END IF;

        -- The peek is authoritative for the next key to process. This is
        -- especially important after exhausting a multi-version key: the
        -- version boundary has been cleared, so executing the batch against
        -- a stale v_next_seek would replay every version of that old key.
        v_next_seek := lower(v_peek_name);
        v_next_seek_strict := false;

        -- STEP 2: Check if this is a FOLDER or FILE
        v_common_prefix := storage.get_common_prefix(lower(v_peek_name), v_prefix_lower, v_delimiter);

        IF v_common_prefix IS NOT NULL THEN
            -- FOLDER: Handle offset, emit if needed, skip to next folder
            IF v_skipped < offsets THEN
                v_skipped := v_skipped + 1;
            ELSE
                name := substring(rtrim(storage.get_common_prefix(v_peek_name, v_prefix, v_delimiter), v_delimiter) from v_prefix_len + 1);
                id := NULL;
                updated_at := NULL;
                created_at := NULL;
                last_accessed_at := NULL;
                metadata := NULL;
                version := NULL;
                archived_at := NULL;
                is_delete_marker := NULL;
                is_versioned := NULL;
                RETURN NEXT;
                v_count := v_count + 1;
            END IF;

            -- Advance seek past the folder range
            IF v_is_asc THEN
                v_next_seek := lower(left(v_common_prefix, -1)) || chr(ascii(v_delimiter) + 1);
            ELSE
                v_next_seek := lower(v_common_prefix);
            END IF;
            v_next_seek_at := NULL;
            v_next_seek_version := '';
        ELSE
            -- FILE: Batch fetch using DYNAMIC SQL (overhead amortized over many rows)
            -- For ASC: upper_bound is the exclusive upper limit (< condition)
            -- For DESC: prefix_lower is the inclusive lower limit (>= condition)
            FOR v_current IN EXECUTE v_batch_query
                USING bucketname, v_next_seek,
                    CASE WHEN v_is_asc THEN COALESCE(v_upper_bound, v_prefix_lower) ELSE v_prefix_lower END, v_file_batch_size,
                    v_next_seek_at, v_next_seek_version
            LOOP
                v_common_prefix := storage.get_common_prefix(lower(v_current.name), v_prefix_lower, v_delimiter);

                IF v_common_prefix IS NOT NULL THEN
                    -- Hit a folder: exit batch, let peek handle it. Reset
                    -- strict mode too - it may have been set by an earlier
                    -- row in this same batch (see the single-row ASC advance
                    -- below), and v_next_seek here is the folder-triggering
                    -- row's own name, which the next peek must find inclusively.
                    v_next_seek := CASE
                        WHEN v_is_asc THEN lower(v_current.name)
                        ELSE lower(v_current.name) || v_delimiter
                    END;
                    v_next_seek_at := NULL;
                    v_next_seek_version := '';
                    v_next_seek_strict := false;
                    EXIT;
                END IF;

                -- Handle offset skipping
                IF v_skipped < offsets THEN
                    v_skipped := v_skipped + 1;
                ELSE
                    -- Emit file
                    name := substring(v_current.name from v_prefix_len + 1);
                    id := v_current.id;
                    updated_at := v_current.updated_at;
                    created_at := v_current.created_at;
                    last_accessed_at := v_current.last_accessed_at;
                    metadata := v_current.metadata;
                    version := v_current.version;
                    archived_at := v_current.archived_at;
                    is_delete_marker := v_current.is_delete_marker;
                    is_versioned := v_current.is_versioned;
                    RETURN NEXT;
                    v_count := v_count + 1;
                END IF;

                -- Multi-row mode must remain on this key until all of its
                -- versions have crossed the internal batch boundary.
                IF v_multi_row THEN
                    v_next_seek := lower(v_current.name);
                    v_next_seek_at := COALESCE(v_current.archived_at, 'infinity'::timestamptz);
                    v_next_seek_version := COALESCE(v_current.version, '');
                ELSIF v_is_asc THEN
                    -- Appending the delimiter as a fake lexical successor would
                    -- skip a real key like `name || '!'` (or any character
                    -- sorting below the delimiter), which sorts between `name`
                    -- and `name || delimiter`. Track the real name and mark the
                    -- next comparison strict instead - same fix as the
                    -- exhausted-key case above.
                    v_next_seek := lower(v_current.name);
                    v_next_seek_strict := true;
                ELSE
                    v_next_seek := lower(v_current.name);
                END IF;

                EXIT WHEN v_count >= v_limit;
            END LOOP;
        END IF;

        IF v_count = v_previous_count
           AND v_skipped = v_previous_skipped
           AND v_next_seek IS NOT DISTINCT FROM v_previous_seek
           AND v_next_seek_at IS NOT DISTINCT FROM v_previous_seek_at
           AND v_next_seek_version IS NOT DISTINCT FROM v_previous_seek_version THEN
            RAISE EXCEPTION 'storage.search made no progress at seek (%, %, %)',
                v_next_seek, v_next_seek_at, v_next_seek_version;
        END IF;
    END LOOP;
END;
$_$;


ALTER FUNCTION storage.search(prefix text, bucketname text, limits integer, levels integer, offsets integer, search text, sortcolumn text, sortorder text, noncurrent_versions text, delete_markers text) OWNER TO supabase_storage_admin;

--
-- Name: search_by_timestamp(text, text, integer, integer, text, text, text, text, text, text, text); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.search_by_timestamp(p_prefix text, p_bucket_id text, p_limit integer, p_level integer, p_start_after text, p_sort_order text, p_sort_column text, p_sort_column_after text, noncurrent_versions text DEFAULT 'exclude'::text, delete_markers text DEFAULT 'exclude'::text, p_start_after_version text DEFAULT ''::text) RETURNS TABLE(key text, name text, id uuid, updated_at timestamp with time zone, created_at timestamp with time zone, last_accessed_at timestamp with time zone, metadata jsonb, version text, archived_at timestamp with time zone, is_delete_marker boolean, is_versioned boolean)
    LANGUAGE plpgsql STABLE
    AS $_$
DECLARE
    v_cursor_op text;
    v_query text;
    v_prefix text;
    v_prefix_pattern text;
    v_sort_order text;
    v_sort_column text;
    v_version_tiebreak text;
BEGIN
    v_prefix := coalesce(p_prefix, '');
    -- Keep the raw prefix for common-prefix calculations and escape only LIKE metacharacters.
    v_prefix_pattern := replace(v_prefix, chr(92), chr(92) || chr(92));
    v_prefix_pattern := replace(v_prefix_pattern, '%', chr(92) || '%');
    v_prefix_pattern := replace(v_prefix_pattern, '_', chr(92) || '_');

    -- COALESCE first: NULL NOT IN (...) evaluates to NULL (not TRUE), so a
    -- bare NOT IN check silently leaves an explicit NULL argument unreset.
    noncurrent_versions := COALESCE(noncurrent_versions, 'exclude');
    delete_markers := COALESCE(delete_markers, 'exclude');
    IF noncurrent_versions NOT IN ('exclude', 'only', 'include') THEN
        noncurrent_versions := 'exclude';
    END IF;
    IF delete_markers NOT IN ('exclude', 'only', 'include') THEN
        delete_markers := 'exclude';
    END IF;

    -- $9 is only populated in multi-row mode; it's always '' otherwise, so
    -- only use each row's real version as a tiebreak in multi-row mode.
    v_version_tiebreak := CASE WHEN noncurrent_versions IN ('only', 'include') THEN 'COALESCE(version, '''')' ELSE '''''' END;

    -- Defense-in-depth: this function is independently reachable and must
    -- not trust p_sort_order/p_sort_column to already be validated by a
    -- caller. Normalize to the same strict allow-list storage.search_v2
    -- uses before interpolating anything into dynamic SQL below.
    v_sort_order := lower(coalesce(p_sort_order, 'asc'));
    IF v_sort_order NOT IN ('asc', 'desc') THEN
        v_sort_order := 'asc';
    END IF;

    v_sort_column := lower(coalesce(p_sort_column, 'updated_at'));
    IF v_sort_column NOT IN ('updated_at', 'created_at') THEN
        v_sort_column := 'updated_at';
    END IF;

    IF v_sort_order = 'asc' THEN
        v_cursor_op := '>';
    ELSE
        v_cursor_op := '<';
    END IF;

    v_query := format($sql$
        WITH raw_objects AS (
            SELECT
                o.name AS obj_name,
                o.id AS obj_id,
                o.updated_at AS obj_updated_at,
                o.created_at AS obj_created_at,
                o.last_accessed_at AS obj_last_accessed_at,
                o.metadata AS obj_metadata,
                o.version AS obj_version,
                o.archived_at AS obj_archived_at,
                o.is_delete_marker AS obj_is_delete_marker,
                o.is_versioned AS obj_is_versioned,
                storage.get_common_prefix(o.name, $1, '/') AS common_prefix
            FROM storage.objects o
            WHERE o.bucket_id = $2
              AND o.name COLLATE "C" LIKE $10 || '%%'
              AND ($7 != 'exclude' OR o.archived_at IS NULL)
              AND ($7 != 'only' OR o.archived_at IS NOT NULL)
              AND ($8 != 'exclude' OR NOT o.is_delete_marker)
              AND ($8 != 'only' OR o.is_delete_marker)
        ),
        -- Aggregate common prefixes (folders)
        -- Both created_at and updated_at use MIN(obj_created_at) to match the old prefixes table behavior
        aggregated_prefixes AS (
            SELECT
                common_prefix AS name,
                NULL::uuid AS id,
                MIN(obj_created_at) AS updated_at,
                MIN(obj_created_at) AS created_at,
                NULL::timestamptz AS last_accessed_at,
                NULL::jsonb AS metadata,
                NULL::text AS version,
                NULL::timestamptz AS archived_at,
                NULL::boolean AS is_delete_marker,
                NULL::boolean AS is_versioned,
                TRUE AS is_prefix
            FROM raw_objects
            WHERE common_prefix IS NOT NULL
            GROUP BY common_prefix
        ),
        leaf_objects AS (
            SELECT
                obj_name AS name,
                obj_id AS id,
                obj_updated_at AS updated_at,
                obj_created_at AS created_at,
                obj_last_accessed_at AS last_accessed_at,
                obj_metadata AS metadata,
                obj_version AS version,
                obj_archived_at AS archived_at,
                obj_is_delete_marker AS is_delete_marker,
                obj_is_versioned AS is_versioned,
                FALSE AS is_prefix
            FROM raw_objects
            WHERE common_prefix IS NULL
        ),
        combined AS (
            SELECT * FROM aggregated_prefixes
            UNION ALL
            SELECT * FROM leaf_objects
        ),
        filtered AS (
            SELECT *
            FROM combined
            WHERE (
                $5 = ''
                OR ROW(
                    COALESCE(date_trunc('milliseconds', %I), 'epoch'::timestamptz),
                    name COLLATE "C",
                    %s
                ) %s ROW(
                    -- truncated the same way as the stored value above
                    date_trunc('milliseconds', COALESCE(NULLIF($6, '')::timestamptz, 'epoch'::timestamptz)),
                    $5,
                    $9
                )
            )
        )
        SELECT
            split_part(name, '/', $3) AS key,
            name,
            id,
            updated_at,
            created_at,
            last_accessed_at,
            metadata,
            version,
            archived_at,
            is_delete_marker,
            is_versioned
        FROM filtered
        ORDER BY
            COALESCE(date_trunc('milliseconds', %I), 'epoch'::timestamptz) %s,
            name COLLATE "C" %s,
            COALESCE(version, '') %s
        LIMIT $4
    $sql$,
        v_sort_column,
        v_version_tiebreak,
        v_cursor_op,
        v_sort_column,
        v_sort_order,
        v_sort_order,
        v_sort_order
    );

    -- version is the third tiebreak component for two versions of the same
    -- key tying on both timestamp and name (see filtered CTE / ORDER BY above)
    RETURN QUERY EXECUTE v_query
    USING v_prefix, p_bucket_id, p_level, p_limit, p_start_after, p_sort_column_after, noncurrent_versions, delete_markers, coalesce(p_start_after_version, ''), v_prefix_pattern;
END;
$_$;


ALTER FUNCTION storage.search_by_timestamp(p_prefix text, p_bucket_id text, p_limit integer, p_level integer, p_start_after text, p_sort_order text, p_sort_column text, p_sort_column_after text, noncurrent_versions text, delete_markers text, p_start_after_version text) OWNER TO supabase_storage_admin;

--
-- Name: search_v2(text, text, integer, integer, text, text, text, text, text, text, timestamp with time zone, text, boolean); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.search_v2(prefix text, bucket_name text, limits integer DEFAULT 100, levels integer DEFAULT 1, start_after text DEFAULT ''::text, sort_order text DEFAULT 'asc'::text, sort_column text DEFAULT 'name'::text, sort_column_after text DEFAULT ''::text, noncurrent_versions text DEFAULT 'exclude'::text, delete_markers text DEFAULT 'exclude'::text, start_after_archived_at timestamp with time zone DEFAULT NULL::timestamp with time zone, start_after_version text DEFAULT ''::text, start_after_is_continuation boolean DEFAULT false) RETURNS TABLE(key text, name text, id uuid, updated_at timestamp with time zone, created_at timestamp with time zone, last_accessed_at timestamp with time zone, metadata jsonb, version text, archived_at timestamp with time zone, is_delete_marker boolean, is_versioned boolean)
    LANGUAGE plpgsql STABLE
    AS $$
DECLARE
    v_sort_col text;
    v_sort_ord text;
    v_limit int;
BEGIN
    -- Cap limit to maximum of 1500 records
    v_limit := LEAST(coalesce(limits, 100), 1500);

    -- Validate and normalize sort_order
    v_sort_ord := lower(coalesce(sort_order, 'asc'));
    IF v_sort_ord NOT IN ('asc', 'desc') THEN
        v_sort_ord := 'asc';
    END IF;

    -- Validate and normalize sort_column
    v_sort_col := lower(coalesce(sort_column, 'name'));
    IF v_sort_col NOT IN ('name', 'updated_at', 'created_at') THEN
        v_sort_col := 'name';
    END IF;

    -- Route to appropriate implementation
    IF v_sort_col = 'name' THEN
        -- Use list_objects_with_delimiter for name sorting (most efficient: O(k * log n))
        RETURN QUERY
        SELECT
            split_part(l.name, '/', levels) AS key,
            l.name AS name,
            l.id,
            l.updated_at,
            l.created_at,
            l.last_accessed_at,
            l.metadata,
            l.version,
            l.archived_at,
            l.is_delete_marker,
            l.is_versioned
        FROM storage.list_objects_with_delimiter(
            bucket_name,
            coalesce(prefix, ''),
            '/',
            v_limit,
            CASE WHEN start_after_is_continuation THEN '' ELSE start_after END,
            CASE WHEN start_after_is_continuation THEN start_after ELSE '' END,
            v_sort_ord,
            noncurrent_versions,
            delete_markers,
            start_after_archived_at,
            start_after_version
        ) l;
    ELSE
        -- Use aggregation approach for timestamp sorting
        -- Not efficient for large datasets but supports correct pagination
        RETURN QUERY SELECT * FROM storage.search_by_timestamp(
            prefix, bucket_name, v_limit, levels, start_after,
            v_sort_ord, v_sort_col, sort_column_after,
            noncurrent_versions, delete_markers, start_after_version
        );
    END IF;
END;
$$;


ALTER FUNCTION storage.search_v2(prefix text, bucket_name text, limits integer, levels integer, start_after text, sort_order text, sort_column text, sort_column_after text, noncurrent_versions text, delete_markers text, start_after_archived_at timestamp with time zone, start_after_version text, start_after_is_continuation boolean) OWNER TO supabase_storage_admin;

--
-- Name: update_updated_at_column(); Type: FUNCTION; Schema: storage; Owner: supabase_storage_admin
--

CREATE FUNCTION storage.update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW; 
END;
$$;


ALTER FUNCTION storage.update_updated_at_column() OWNER TO supabase_storage_admin;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: audit_log_entries; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.audit_log_entries (
    instance_id uuid,
    id uuid NOT NULL,
    payload json,
    created_at timestamp with time zone,
    ip_address character varying(64) DEFAULT ''::character varying NOT NULL
);


ALTER TABLE auth.audit_log_entries OWNER TO supabase_auth_admin;

--
-- Name: TABLE audit_log_entries; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.audit_log_entries IS 'Auth: Audit trail for user actions.';


--
-- Name: custom_oauth_providers; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.custom_oauth_providers (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    provider_type text NOT NULL,
    identifier text NOT NULL,
    name text NOT NULL,
    client_id text NOT NULL,
    client_secret text NOT NULL,
    acceptable_client_ids text[] DEFAULT '{}'::text[] NOT NULL,
    scopes text[] DEFAULT '{}'::text[] NOT NULL,
    pkce_enabled boolean DEFAULT true NOT NULL,
    attribute_mapping jsonb DEFAULT '{}'::jsonb NOT NULL,
    authorization_params jsonb DEFAULT '{}'::jsonb NOT NULL,
    enabled boolean DEFAULT true NOT NULL,
    email_optional boolean DEFAULT false NOT NULL,
    issuer text,
    discovery_url text,
    skip_nonce_check boolean DEFAULT false NOT NULL,
    cached_discovery jsonb,
    discovery_cached_at timestamp with time zone,
    authorization_url text,
    token_url text,
    userinfo_url text,
    jwks_uri text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    custom_claims_allowlist text[] DEFAULT '{}'::text[] NOT NULL,
    CONSTRAINT custom_oauth_providers_authorization_url_https CHECK (((authorization_url IS NULL) OR (authorization_url ~~ 'https://%'::text))),
    CONSTRAINT custom_oauth_providers_authorization_url_length CHECK (((authorization_url IS NULL) OR (char_length(authorization_url) <= 2048))),
    CONSTRAINT custom_oauth_providers_client_id_length CHECK (((char_length(client_id) >= 1) AND (char_length(client_id) <= 512))),
    CONSTRAINT custom_oauth_providers_discovery_url_length CHECK (((discovery_url IS NULL) OR (char_length(discovery_url) <= 2048))),
    CONSTRAINT custom_oauth_providers_identifier_format CHECK ((identifier ~ '^[a-z0-9][a-z0-9:-]{0,48}[a-z0-9]$'::text)),
    CONSTRAINT custom_oauth_providers_issuer_length CHECK (((issuer IS NULL) OR ((char_length(issuer) >= 1) AND (char_length(issuer) <= 2048)))),
    CONSTRAINT custom_oauth_providers_jwks_uri_https CHECK (((jwks_uri IS NULL) OR (jwks_uri ~~ 'https://%'::text))),
    CONSTRAINT custom_oauth_providers_jwks_uri_length CHECK (((jwks_uri IS NULL) OR (char_length(jwks_uri) <= 2048))),
    CONSTRAINT custom_oauth_providers_name_length CHECK (((char_length(name) >= 1) AND (char_length(name) <= 100))),
    CONSTRAINT custom_oauth_providers_oauth2_requires_endpoints CHECK (((provider_type <> 'oauth2'::text) OR ((authorization_url IS NOT NULL) AND (token_url IS NOT NULL) AND (userinfo_url IS NOT NULL)))),
    CONSTRAINT custom_oauth_providers_oidc_discovery_url_https CHECK (((provider_type <> 'oidc'::text) OR (discovery_url IS NULL) OR (discovery_url ~~ 'https://%'::text))),
    CONSTRAINT custom_oauth_providers_oidc_issuer_https CHECK (((provider_type <> 'oidc'::text) OR (issuer IS NULL) OR (issuer ~~ 'https://%'::text))),
    CONSTRAINT custom_oauth_providers_oidc_requires_issuer CHECK (((provider_type <> 'oidc'::text) OR (issuer IS NOT NULL))),
    CONSTRAINT custom_oauth_providers_provider_type_check CHECK ((provider_type = ANY (ARRAY['oauth2'::text, 'oidc'::text]))),
    CONSTRAINT custom_oauth_providers_token_url_https CHECK (((token_url IS NULL) OR (token_url ~~ 'https://%'::text))),
    CONSTRAINT custom_oauth_providers_token_url_length CHECK (((token_url IS NULL) OR (char_length(token_url) <= 2048))),
    CONSTRAINT custom_oauth_providers_userinfo_url_https CHECK (((userinfo_url IS NULL) OR (userinfo_url ~~ 'https://%'::text))),
    CONSTRAINT custom_oauth_providers_userinfo_url_length CHECK (((userinfo_url IS NULL) OR (char_length(userinfo_url) <= 2048)))
);


ALTER TABLE auth.custom_oauth_providers OWNER TO supabase_auth_admin;

--
-- Name: flow_state; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.flow_state (
    id uuid NOT NULL,
    user_id uuid,
    auth_code text,
    code_challenge_method auth.code_challenge_method,
    code_challenge text,
    provider_type text NOT NULL,
    provider_access_token text,
    provider_refresh_token text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    authentication_method text NOT NULL,
    auth_code_issued_at timestamp with time zone,
    invite_token text,
    referrer text,
    oauth_client_state_id uuid,
    linking_target_id uuid,
    email_optional boolean DEFAULT false NOT NULL
);


ALTER TABLE auth.flow_state OWNER TO supabase_auth_admin;

--
-- Name: TABLE flow_state; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.flow_state IS 'Stores metadata for all OAuth/SSO login flows';


--
-- Name: identities; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.identities (
    provider_id text NOT NULL,
    user_id uuid NOT NULL,
    identity_data jsonb NOT NULL,
    provider text NOT NULL,
    last_sign_in_at timestamp with time zone,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    email text GENERATED ALWAYS AS (lower((identity_data ->> 'email'::text))) STORED,
    id uuid DEFAULT gen_random_uuid() NOT NULL
);


ALTER TABLE auth.identities OWNER TO supabase_auth_admin;

--
-- Name: TABLE identities; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.identities IS 'Auth: Stores identities associated to a user.';


--
-- Name: COLUMN identities.email; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.identities.email IS 'Auth: Email is a generated column that references the optional email property in the identity_data';


--
-- Name: instances; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.instances (
    id uuid NOT NULL,
    uuid uuid,
    raw_base_config text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


ALTER TABLE auth.instances OWNER TO supabase_auth_admin;

--
-- Name: TABLE instances; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.instances IS 'Auth: Manages users across multiple sites.';


--
-- Name: mfa_amr_claims; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.mfa_amr_claims (
    session_id uuid NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL,
    authentication_method text NOT NULL,
    id uuid NOT NULL
);


ALTER TABLE auth.mfa_amr_claims OWNER TO supabase_auth_admin;

--
-- Name: TABLE mfa_amr_claims; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.mfa_amr_claims IS 'auth: stores authenticator method reference claims for multi factor authentication';


--
-- Name: mfa_challenges; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.mfa_challenges (
    id uuid NOT NULL,
    factor_id uuid NOT NULL,
    created_at timestamp with time zone NOT NULL,
    verified_at timestamp with time zone,
    ip_address inet NOT NULL,
    otp_code text,
    web_authn_session_data jsonb
);


ALTER TABLE auth.mfa_challenges OWNER TO supabase_auth_admin;

--
-- Name: TABLE mfa_challenges; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.mfa_challenges IS 'auth: stores metadata about challenge requests made';


--
-- Name: mfa_factors; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.mfa_factors (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    friendly_name text,
    factor_type auth.factor_type NOT NULL,
    status auth.factor_status NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL,
    secret text,
    phone text,
    last_challenged_at timestamp with time zone,
    web_authn_credential jsonb,
    web_authn_aaguid uuid,
    last_webauthn_challenge_data jsonb
);


ALTER TABLE auth.mfa_factors OWNER TO supabase_auth_admin;

--
-- Name: TABLE mfa_factors; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.mfa_factors IS 'auth: stores metadata about factors';


--
-- Name: COLUMN mfa_factors.last_webauthn_challenge_data; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.mfa_factors.last_webauthn_challenge_data IS 'Stores the latest WebAuthn challenge data including attestation/assertion for customer verification';


--
-- Name: mfa_recovery_code_sets; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.mfa_recovery_code_sets (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    mfa_factor_id uuid NOT NULL,
    failed_verification_count integer DEFAULT 0 NOT NULL,
    verification_locked_until timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT mfa_recovery_code_sets_failed_verification_count_check CHECK ((failed_verification_count >= 0))
);


ALTER TABLE auth.mfa_recovery_code_sets OWNER TO supabase_auth_admin;

--
-- Name: mfa_recovery_codes; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.mfa_recovery_codes (
    id uuid NOT NULL,
    mfa_recovery_code_set_id uuid NOT NULL,
    code_hash text NOT NULL,
    consumed_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE auth.mfa_recovery_codes OWNER TO supabase_auth_admin;

--
-- Name: oauth_authorizations; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.oauth_authorizations (
    id uuid NOT NULL,
    authorization_id text NOT NULL,
    client_id uuid NOT NULL,
    user_id uuid,
    redirect_uri text NOT NULL,
    scope text NOT NULL,
    state text,
    resource text,
    code_challenge text,
    code_challenge_method auth.code_challenge_method,
    response_type auth.oauth_response_type DEFAULT 'code'::auth.oauth_response_type NOT NULL,
    status auth.oauth_authorization_status DEFAULT 'pending'::auth.oauth_authorization_status NOT NULL,
    authorization_code text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    expires_at timestamp with time zone DEFAULT (now() + '00:03:00'::interval) NOT NULL,
    approved_at timestamp with time zone,
    nonce text,
    CONSTRAINT oauth_authorizations_authorization_code_length CHECK ((char_length(authorization_code) <= 255)),
    CONSTRAINT oauth_authorizations_code_challenge_length CHECK ((char_length(code_challenge) <= 128)),
    CONSTRAINT oauth_authorizations_expires_at_future CHECK ((expires_at > created_at)),
    CONSTRAINT oauth_authorizations_nonce_length CHECK ((char_length(nonce) <= 255)),
    CONSTRAINT oauth_authorizations_redirect_uri_length CHECK ((char_length(redirect_uri) <= 2048)),
    CONSTRAINT oauth_authorizations_resource_length CHECK ((char_length(resource) <= 2048)),
    CONSTRAINT oauth_authorizations_scope_length CHECK ((char_length(scope) <= 4096)),
    CONSTRAINT oauth_authorizations_state_length CHECK ((char_length(state) <= 4096))
);


ALTER TABLE auth.oauth_authorizations OWNER TO supabase_auth_admin;

--
-- Name: oauth_client_states; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.oauth_client_states (
    id uuid NOT NULL,
    provider_type text NOT NULL,
    code_verifier text,
    created_at timestamp with time zone NOT NULL
);


ALTER TABLE auth.oauth_client_states OWNER TO supabase_auth_admin;

--
-- Name: TABLE oauth_client_states; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.oauth_client_states IS 'Stores OAuth states for third-party provider authentication flows where Supabase acts as the OAuth client.';


--
-- Name: oauth_clients; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.oauth_clients (
    id uuid NOT NULL,
    client_secret_hash text,
    registration_type auth.oauth_registration_type NOT NULL,
    redirect_uris text NOT NULL,
    grant_types text NOT NULL,
    client_name text,
    client_uri text,
    logo_uri text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone,
    client_type auth.oauth_client_type DEFAULT 'confidential'::auth.oauth_client_type NOT NULL,
    token_endpoint_auth_method text NOT NULL,
    CONSTRAINT oauth_clients_client_name_length CHECK ((char_length(client_name) <= 1024)),
    CONSTRAINT oauth_clients_client_uri_length CHECK ((char_length(client_uri) <= 2048)),
    CONSTRAINT oauth_clients_logo_uri_length CHECK ((char_length(logo_uri) <= 2048)),
    CONSTRAINT oauth_clients_token_endpoint_auth_method_check CHECK ((token_endpoint_auth_method = ANY (ARRAY['client_secret_basic'::text, 'client_secret_post'::text, 'none'::text])))
);


ALTER TABLE auth.oauth_clients OWNER TO supabase_auth_admin;

--
-- Name: oauth_consents; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.oauth_consents (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    client_id uuid NOT NULL,
    scopes text NOT NULL,
    granted_at timestamp with time zone DEFAULT now() NOT NULL,
    revoked_at timestamp with time zone,
    CONSTRAINT oauth_consents_revoked_after_granted CHECK (((revoked_at IS NULL) OR (revoked_at >= granted_at))),
    CONSTRAINT oauth_consents_scopes_length CHECK ((char_length(scopes) <= 2048)),
    CONSTRAINT oauth_consents_scopes_not_empty CHECK ((char_length(TRIM(BOTH FROM scopes)) > 0))
);


ALTER TABLE auth.oauth_consents OWNER TO supabase_auth_admin;

--
-- Name: one_time_tokens; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.one_time_tokens (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    token_type auth.one_time_token_type NOT NULL,
    token_hash text NOT NULL,
    relates_to text NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    expires_at timestamp with time zone,
    CONSTRAINT one_time_tokens_token_hash_check CHECK ((char_length(token_hash) > 0))
);


ALTER TABLE auth.one_time_tokens OWNER TO supabase_auth_admin;

--
-- Name: refresh_tokens; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.refresh_tokens (
    instance_id uuid,
    id bigint NOT NULL,
    token character varying(255),
    user_id character varying(255),
    revoked boolean,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    parent character varying(255),
    session_id uuid
);


ALTER TABLE auth.refresh_tokens OWNER TO supabase_auth_admin;

--
-- Name: TABLE refresh_tokens; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.refresh_tokens IS 'Auth: Store of tokens used to refresh JWT tokens once they expire.';


--
-- Name: refresh_tokens_id_seq; Type: SEQUENCE; Schema: auth; Owner: supabase_auth_admin
--

CREATE SEQUENCE auth.refresh_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE auth.refresh_tokens_id_seq OWNER TO supabase_auth_admin;

--
-- Name: refresh_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: auth; Owner: supabase_auth_admin
--

ALTER SEQUENCE auth.refresh_tokens_id_seq OWNED BY auth.refresh_tokens.id;


--
-- Name: saml_providers; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.saml_providers (
    id uuid NOT NULL,
    sso_provider_id uuid NOT NULL,
    entity_id text NOT NULL,
    metadata_xml text NOT NULL,
    metadata_url text,
    attribute_mapping jsonb,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    name_id_format text,
    CONSTRAINT "entity_id not empty" CHECK ((char_length(entity_id) > 0)),
    CONSTRAINT "metadata_url not empty" CHECK (((metadata_url = NULL::text) OR (char_length(metadata_url) > 0))),
    CONSTRAINT "metadata_xml not empty" CHECK ((char_length(metadata_xml) > 0))
);


ALTER TABLE auth.saml_providers OWNER TO supabase_auth_admin;

--
-- Name: TABLE saml_providers; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.saml_providers IS 'Auth: Manages SAML Identity Provider connections.';


--
-- Name: saml_relay_states; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.saml_relay_states (
    id uuid NOT NULL,
    sso_provider_id uuid NOT NULL,
    request_id text NOT NULL,
    for_email text,
    redirect_to text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    flow_state_id uuid,
    CONSTRAINT "request_id not empty" CHECK ((char_length(request_id) > 0))
);


ALTER TABLE auth.saml_relay_states OWNER TO supabase_auth_admin;

--
-- Name: TABLE saml_relay_states; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.saml_relay_states IS 'Auth: Contains SAML Relay State information for each Service Provider initiated login.';


--
-- Name: schema_migrations; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.schema_migrations (
    version character varying(255) NOT NULL
);


ALTER TABLE auth.schema_migrations OWNER TO supabase_auth_admin;

--
-- Name: TABLE schema_migrations; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.schema_migrations IS 'Auth: Manages updates to the auth system.';


--
-- Name: scim_tokens; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.scim_tokens (
    id uuid NOT NULL,
    sso_provider_id uuid NOT NULL,
    token_hash text NOT NULL,
    prefix text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    expires_at timestamp with time zone,
    revoked_at timestamp with time zone,
    last_used_at timestamp with time zone,
    CONSTRAINT scim_tokens_expires_at_future CHECK (((expires_at IS NULL) OR (expires_at > created_at))),
    CONSTRAINT scim_tokens_revoked_after_created CHECK (((revoked_at IS NULL) OR (revoked_at >= created_at))),
    CONSTRAINT scim_tokens_token_hash_check CHECK ((token_hash ~ '^[0-9a-f]{64}$'::text))
);


ALTER TABLE auth.scim_tokens OWNER TO supabase_auth_admin;

--
-- Name: scim_users; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.scim_users (
    id uuid NOT NULL,
    sso_provider_id uuid NOT NULL,
    user_id uuid,
    resource jsonb NOT NULL,
    user_name text GENERATED ALWAYS AS (lower((resource ->> 'userName'::text))) STORED NOT NULL,
    external_id text GENERATED ALWAYS AS ((resource ->> 'externalId'::text)) STORED,
    active boolean GENERATED ALWAYS AS (COALESCE(((resource ->> 'active'::text))::boolean, true)) STORED NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


ALTER TABLE auth.scim_users OWNER TO supabase_auth_admin;

--
-- Name: sessions; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.sessions (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    factor_id uuid,
    aal auth.aal_level,
    not_after timestamp with time zone,
    refreshed_at timestamp without time zone,
    user_agent text,
    ip inet,
    tag text,
    oauth_client_id uuid,
    refresh_token_hmac_key text,
    refresh_token_counter bigint,
    scopes text,
    CONSTRAINT sessions_scopes_length CHECK ((char_length(scopes) <= 4096))
);


ALTER TABLE auth.sessions OWNER TO supabase_auth_admin;

--
-- Name: TABLE sessions; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.sessions IS 'Auth: Stores session data associated to a user.';


--
-- Name: COLUMN sessions.not_after; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.sessions.not_after IS 'Auth: Not after is a nullable column that contains a timestamp after which the session should be regarded as expired.';


--
-- Name: COLUMN sessions.refresh_token_hmac_key; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.sessions.refresh_token_hmac_key IS 'Holds a HMAC-SHA256 key used to sign refresh tokens for this session.';


--
-- Name: COLUMN sessions.refresh_token_counter; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.sessions.refresh_token_counter IS 'Holds the ID (counter) of the last issued refresh token.';


--
-- Name: sso_domains; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.sso_domains (
    id uuid NOT NULL,
    sso_provider_id uuid NOT NULL,
    domain text NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT "domain not empty" CHECK ((char_length(domain) > 0))
);


ALTER TABLE auth.sso_domains OWNER TO supabase_auth_admin;

--
-- Name: TABLE sso_domains; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.sso_domains IS 'Auth: Manages SSO email address domain mapping to an SSO Identity Provider.';


--
-- Name: sso_providers; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.sso_providers (
    id uuid NOT NULL,
    resource_id text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    disabled boolean,
    CONSTRAINT "resource_id not empty" CHECK (((resource_id = NULL::text) OR (char_length(resource_id) > 0)))
);


ALTER TABLE auth.sso_providers OWNER TO supabase_auth_admin;

--
-- Name: TABLE sso_providers; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.sso_providers IS 'Auth: Manages SSO identity provider information; see saml_providers for SAML.';


--
-- Name: COLUMN sso_providers.resource_id; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.sso_providers.resource_id IS 'Auth: Uniquely identifies a SSO provider according to a user-chosen resource ID (case insensitive), useful in infrastructure as code.';


--
-- Name: users; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.users (
    instance_id uuid,
    id uuid NOT NULL,
    aud character varying(255),
    role character varying(255),
    email character varying(255),
    encrypted_password character varying(255),
    email_confirmed_at timestamp with time zone,
    invited_at timestamp with time zone,
    confirmation_token character varying(255),
    confirmation_sent_at timestamp with time zone,
    recovery_token character varying(255),
    recovery_sent_at timestamp with time zone,
    email_change_token_new character varying(255),
    email_change character varying(255),
    email_change_sent_at timestamp with time zone,
    last_sign_in_at timestamp with time zone,
    raw_app_meta_data jsonb,
    raw_user_meta_data jsonb,
    is_super_admin boolean,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    phone text DEFAULT NULL::character varying,
    phone_confirmed_at timestamp with time zone,
    phone_change text DEFAULT ''::character varying,
    phone_change_token character varying(255) DEFAULT ''::character varying,
    phone_change_sent_at timestamp with time zone,
    confirmed_at timestamp with time zone GENERATED ALWAYS AS (LEAST(email_confirmed_at, phone_confirmed_at)) STORED,
    email_change_token_current character varying(255) DEFAULT ''::character varying,
    email_change_confirm_status smallint DEFAULT 0,
    banned_until timestamp with time zone,
    reauthentication_token character varying(255) DEFAULT ''::character varying,
    reauthentication_sent_at timestamp with time zone,
    is_sso_user boolean DEFAULT false NOT NULL,
    deleted_at timestamp with time zone,
    is_anonymous boolean DEFAULT false NOT NULL,
    CONSTRAINT users_email_change_confirm_status_check CHECK (((email_change_confirm_status >= 0) AND (email_change_confirm_status <= 2)))
);


ALTER TABLE auth.users OWNER TO supabase_auth_admin;

--
-- Name: TABLE users; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON TABLE auth.users IS 'Auth: Stores user login data within a secure schema.';


--
-- Name: COLUMN users.is_sso_user; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON COLUMN auth.users.is_sso_user IS 'Auth: Set this column to true when the account comes from SSO. These accounts can have duplicate emails.';


--
-- Name: webauthn_challenges; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.webauthn_challenges (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid,
    challenge_type text NOT NULL,
    session_data jsonb NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    expires_at timestamp with time zone NOT NULL,
    CONSTRAINT webauthn_challenges_challenge_type_check CHECK ((challenge_type = ANY (ARRAY['signup'::text, 'registration'::text, 'authentication'::text])))
);


ALTER TABLE auth.webauthn_challenges OWNER TO supabase_auth_admin;

--
-- Name: webauthn_credentials; Type: TABLE; Schema: auth; Owner: supabase_auth_admin
--

CREATE TABLE auth.webauthn_credentials (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    credential_id bytea NOT NULL,
    public_key bytea NOT NULL,
    attestation_type text DEFAULT ''::text NOT NULL,
    aaguid uuid,
    sign_count bigint DEFAULT 0 NOT NULL,
    transports jsonb DEFAULT '[]'::jsonb NOT NULL,
    backup_eligible boolean DEFAULT false NOT NULL,
    backed_up boolean DEFAULT false NOT NULL,
    friendly_name text DEFAULT ''::text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    last_used_at timestamp with time zone
);


ALTER TABLE auth.webauthn_credentials OWNER TO supabase_auth_admin;

--
-- Name: bitacora_auditoria; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bitacora_auditoria (
    id_evento bigint NOT NULL,
    id_usuario bigint,
    id_tienda bigint,
    entidad character varying(100) NOT NULL,
    accion character varying(100) NOT NULL,
    valor_anterior jsonb,
    valor_nuevo jsonb,
    resultado character varying(50),
    ip_origen inet,
    fecha timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.bitacora_auditoria OWNER TO postgres;

--
-- Name: bitacora_auditoria_id_evento_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.bitacora_auditoria ALTER COLUMN id_evento ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.bitacora_auditoria_id_evento_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: categoria; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.categoria (
    id_categoria bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion character varying(250),
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.categoria OWNER TO postgres;

--
-- Name: categoria_id_categoria_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.categoria ALTER COLUMN id_categoria ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.categoria_id_categoria_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: impuesto; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.impuesto (
    id_impuesto bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    porcentaje numeric(5,2) NOT NULL,
    descripcion character varying(250),
    estado boolean DEFAULT true NOT NULL,
    CONSTRAINT chk_impuesto_porcentaje CHECK (((porcentaje >= (0)::numeric) AND (porcentaje <= (100)::numeric)))
);


ALTER TABLE public.impuesto OWNER TO postgres;

--
-- Name: impuesto_id_impuesto_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.impuesto ALTER COLUMN id_impuesto ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.impuesto_id_impuesto_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: maquina; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.maquina (
    id_maquina bigint NOT NULL,
    id_tienda bigint NOT NULL,
    codigo character varying(100) NOT NULL,
    nombre character varying(150) NOT NULL,
    ubicacion character varying(200),
    modelo character varying(100),
    tipo character varying(100),
    estado boolean DEFAULT true NOT NULL,
    observaciones character varying(500),
    fecha_registro timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.maquina OWNER TO postgres;

--
-- Name: maquina_id_maquina_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.maquina ALTER COLUMN id_maquina ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.maquina_id_maquina_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: maquina_producto; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.maquina_producto (
    id_maquina_producto bigint NOT NULL,
    id_maquina bigint NOT NULL,
    id_producto bigint NOT NULL,
    canal character varying(50) NOT NULL,
    capacidad integer,
    estado boolean DEFAULT true NOT NULL,
    fecha_actualizacion timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT chk_maquina_producto_capacidad CHECK (((capacidad IS NULL) OR (capacidad >= 0)))
);


ALTER TABLE public.maquina_producto OWNER TO postgres;

--
-- Name: maquina_producto_id_maquina_producto_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.maquina_producto ALTER COLUMN id_maquina_producto ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.maquina_producto_id_maquina_producto_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: margen_ganancia; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.margen_ganancia (
    id_margen bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    porcentaje numeric(5,2) NOT NULL,
    descripcion character varying(250),
    estado boolean DEFAULT true NOT NULL,
    CONSTRAINT chk_margen_porcentaje CHECK (((porcentaje >= (0)::numeric) AND (porcentaje <= (100)::numeric)))
);


ALTER TABLE public.margen_ganancia OWNER TO postgres;

--
-- Name: margen_ganancia_id_margen_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.margen_ganancia ALTER COLUMN id_margen ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.margen_ganancia_id_margen_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: precio_tienda; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.precio_tienda (
    id_precio bigint NOT NULL,
    id_producto bigint NOT NULL,
    id_tienda bigint NOT NULL,
    id_margen bigint NOT NULL,
    costo numeric(12,2) NOT NULL,
    precio_con_impuesto numeric(12,2) NOT NULL,
    precio_venta numeric(12,2) NOT NULL,
    fecha_inicio timestamp with time zone DEFAULT now() NOT NULL,
    fecha_fin timestamp with time zone,
    estado boolean DEFAULT true NOT NULL,
    CONSTRAINT chk_precio_con_impuesto CHECK ((precio_con_impuesto >= (0)::numeric)),
    CONSTRAINT chk_precio_costo CHECK ((costo >= (0)::numeric)),
    CONSTRAINT chk_precio_multiplo_25 CHECK ((mod(precio_venta, (25)::numeric) = (0)::numeric)),
    CONSTRAINT chk_precio_venta CHECK ((precio_venta >= (0)::numeric))
);


ALTER TABLE public.precio_tienda OWNER TO postgres;

--
-- Name: precio_tienda_id_precio_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.precio_tienda ALTER COLUMN id_precio ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.precio_tienda_id_precio_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: producto; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.producto (
    id_producto bigint NOT NULL,
    id_categoria bigint NOT NULL,
    id_impuesto bigint NOT NULL,
    codigo_articulo_retail character varying(100),
    codigo_barras character varying(100),
    nombre character varying(150) NOT NULL,
    descripcion character varying(300),
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.producto OWNER TO postgres;

--
-- Name: producto_id_producto_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.producto ALTER COLUMN id_producto ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.producto_id_producto_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: producto_proveedor; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.producto_proveedor (
    id_producto_proveedor bigint NOT NULL,
    id_producto bigint NOT NULL,
    id_proveedor bigint NOT NULL,
    costo numeric(12,2),
    estado boolean DEFAULT true NOT NULL,
    fecha_actualizacion timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT chk_producto_proveedor_costo CHECK (((costo IS NULL) OR (costo >= (0)::numeric)))
);


ALTER TABLE public.producto_proveedor OWNER TO postgres;

--
-- Name: producto_proveedor_id_producto_proveedor_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.producto_proveedor ALTER COLUMN id_producto_proveedor ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.producto_proveedor_id_producto_proveedor_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: proveedor; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.proveedor (
    id_proveedor bigint NOT NULL,
    nombre character varying(150) NOT NULL,
    contacto character varying(150),
    telefono character varying(30),
    correo character varying(150),
    direccion character varying(250),
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.proveedor OWNER TO postgres;

--
-- Name: proveedor_id_proveedor_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.proveedor ALTER COLUMN id_proveedor ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.proveedor_id_proveedor_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: recuperacion_contrasena; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.recuperacion_contrasena (
    id_recuperacion bigint NOT NULL,
    id_usuario bigint NOT NULL,
    token_hash text NOT NULL,
    fecha_solicitud timestamp with time zone DEFAULT now() NOT NULL,
    fecha_expiracion timestamp with time zone NOT NULL,
    fecha_uso timestamp with time zone,
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.recuperacion_contrasena OWNER TO postgres;

--
-- Name: recuperacion_contrasena_id_recuperacion_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.recuperacion_contrasena_id_recuperacion_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.recuperacion_contrasena_id_recuperacion_seq OWNER TO postgres;

--
-- Name: recuperacion_contrasena_id_recuperacion_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.recuperacion_contrasena_id_recuperacion_seq OWNED BY public.recuperacion_contrasena.id_recuperacion;


--
-- Name: relacion_abastecimiento; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.relacion_abastecimiento (
    id_relacion bigint NOT NULL,
    id_tienda_origen bigint NOT NULL,
    id_tienda_destino bigint,
    id_maquina_destino bigint,
    estado boolean DEFAULT true NOT NULL,
    observaciones character varying(500),
    fecha_registro timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT chk_relacion_distinta CHECK (((id_tienda_destino IS NULL) OR (id_tienda_destino <> id_tienda_origen))),
    CONSTRAINT chk_relacion_un_destino CHECK ((((id_tienda_destino IS NOT NULL) AND (id_maquina_destino IS NULL)) OR ((id_tienda_destino IS NULL) AND (id_maquina_destino IS NOT NULL))))
);


ALTER TABLE public.relacion_abastecimiento OWNER TO postgres;

--
-- Name: relacion_abastecimiento_id_relacion_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.relacion_abastecimiento ALTER COLUMN id_relacion ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.relacion_abastecimiento_id_relacion_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: respaldo; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.respaldo (
    id_respaldo bigint NOT NULL,
    id_usuario bigint,
    tipo character varying(30) NOT NULL,
    fecha_inicio timestamp with time zone NOT NULL,
    fecha_fin timestamp with time zone,
    estado character varying(20) NOT NULL,
    cobertura character varying(100) NOT NULL,
    referencia text,
    observaciones text,
    fecha_registro timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT chk_respaldo_estado CHECK (((estado)::text = ANY ((ARRAY['EXITOSO'::character varying, 'FALLIDO'::character varying, 'EN_PROCESO'::character varying])::text[]))),
    CONSTRAINT chk_respaldo_tipo CHECK (((tipo)::text = ANY ((ARRAY['AUTOMATICO'::character varying, 'MANUAL'::character varying])::text[])))
);


ALTER TABLE public.respaldo OWNER TO postgres;

--
-- Name: respaldo_id_respaldo_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.respaldo_id_respaldo_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.respaldo_id_respaldo_seq OWNER TO postgres;

--
-- Name: respaldo_id_respaldo_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.respaldo_id_respaldo_seq OWNED BY public.respaldo.id_respaldo;


--
-- Name: rol; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.rol (
    id_rol bigint NOT NULL,
    nombre character varying(50) NOT NULL,
    descripcion character varying(200),
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.rol OWNER TO postgres;

--
-- Name: rol_id_rol_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.rol ALTER COLUMN id_rol ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.rol_id_rol_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: tienda; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tienda (
    id_tienda bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    ubicacion character varying(200),
    estado boolean DEFAULT true NOT NULL,
    tipo character varying(20) DEFAULT 'TIENDA'::character varying NOT NULL,
    CONSTRAINT chk_tienda_tipo CHECK (((tipo)::text = ANY ((ARRAY['TIENDA'::character varying, 'BODEGA'::character varying])::text[])))
);


ALTER TABLE public.tienda OWNER TO postgres;

--
-- Name: tienda_id_tienda_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.tienda ALTER COLUMN id_tienda ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.tienda_id_tienda_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: usuario; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.usuario (
    id_usuario bigint NOT NULL,
    id_rol bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    apellido character varying(100),
    correo character varying(150) NOT NULL,
    password_hash text NOT NULL,
    estado boolean DEFAULT true NOT NULL,
    ultimo_acceso timestamp with time zone,
    intentos_fallidos integer DEFAULT 0 NOT NULL,
    bloqueado_hasta timestamp with time zone
);


ALTER TABLE public.usuario OWNER TO postgres;

--
-- Name: COLUMN usuario.intentos_fallidos; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.usuario.intentos_fallidos IS 'almacena contador de itentos falidos';


--
-- Name: usuario_id_usuario_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.usuario ALTER COLUMN id_usuario ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.usuario_id_usuario_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: usuario_tienda; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.usuario_tienda (
    id_usuario_tienda bigint NOT NULL,
    id_usuario bigint NOT NULL,
    id_tienda bigint NOT NULL,
    estado boolean DEFAULT true NOT NULL
);


ALTER TABLE public.usuario_tienda OWNER TO postgres;

--
-- Name: usuario_tienda_id_usuario_tienda_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.usuario_tienda ALTER COLUMN id_usuario_tienda ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.usuario_tienda_id_usuario_tienda_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: messages; Type: TABLE; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE TABLE realtime.messages (
    topic text NOT NULL,
    extension text NOT NULL,
    payload jsonb,
    event text,
    private boolean DEFAULT false,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    inserted_at timestamp without time zone DEFAULT now() NOT NULL,
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    binary_payload bytea,
    skip_broadcast boolean DEFAULT false NOT NULL
)
PARTITION BY RANGE (inserted_at);


ALTER TABLE realtime.messages OWNER TO supabase_realtime_admin;

--
-- Name: schema_migrations; Type: TABLE; Schema: realtime; Owner: supabase_admin
--

CREATE TABLE realtime.schema_migrations (
    version bigint NOT NULL,
    inserted_at timestamp(0) without time zone DEFAULT now()
);


ALTER TABLE realtime.schema_migrations OWNER TO supabase_admin;

--
-- Name: subscription; Type: TABLE; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE TABLE realtime.subscription (
    id bigint NOT NULL,
    subscription_id uuid NOT NULL,
    entity regclass NOT NULL,
    filters realtime.user_defined_filter[] DEFAULT '{}'::realtime.user_defined_filter[] NOT NULL,
    claims jsonb NOT NULL,
    claims_role regrole GENERATED ALWAYS AS (realtime.to_regrole((claims ->> 'role'::text))) STORED NOT NULL,
    created_at timestamp without time zone DEFAULT timezone('utc'::text, now()) NOT NULL,
    action_filter text DEFAULT '*'::text,
    selected_columns text[],
    CONSTRAINT subscription_action_filter_check CHECK ((action_filter = ANY (ARRAY['*'::text, 'INSERT'::text, 'UPDATE'::text, 'DELETE'::text])))
);


ALTER TABLE realtime.subscription OWNER TO supabase_realtime_admin;

--
-- Name: subscription_id_seq; Type: SEQUENCE; Schema: realtime; Owner: supabase_realtime_admin
--

ALTER TABLE realtime.subscription ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME realtime.subscription_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: buckets; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.buckets (
    id text NOT NULL,
    name text NOT NULL,
    owner uuid,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    public boolean DEFAULT false,
    avif_autodetection boolean DEFAULT false,
    file_size_limit bigint,
    allowed_mime_types text[],
    owner_id text,
    type storage.buckettype DEFAULT 'STANDARD'::storage.buckettype NOT NULL,
    versioning_status text DEFAULT 'DISABLED'::text NOT NULL,
    lifecycle_configuration jsonb,
    lifecycle_configuration_generation uuid,
    CONSTRAINT buckets_lifecycle_configuration_pair_check CHECK (((lifecycle_configuration IS NULL) = (lifecycle_configuration_generation IS NULL))),
    CONSTRAINT buckets_lifecycle_configuration_shape_check CHECK (((lifecycle_configuration IS NULL) OR ((jsonb_typeof(lifecycle_configuration) = 'object'::text) AND (lifecycle_configuration ? 'rules'::text) AND
CASE
    WHEN (jsonb_typeof((lifecycle_configuration -> 'rules'::text)) = 'array'::text) THEN ((jsonb_array_length((lifecycle_configuration -> 'rules'::text)) >= 1) AND (jsonb_array_length((lifecycle_configuration -> 'rules'::text)) <= 1000))
    ELSE false
END))),
    CONSTRAINT buckets_lifecycle_configuration_standard_only_check CHECK (((type = 'STANDARD'::storage.buckettype) OR ((lifecycle_configuration IS NULL) AND (lifecycle_configuration_generation IS NULL)))),
    CONSTRAINT buckets_versioning_dark_check CHECK ((versioning_status = 'DISABLED'::text)),
    CONSTRAINT buckets_versioning_standard_only_check CHECK (((type = 'STANDARD'::storage.buckettype) OR (versioning_status = 'DISABLED'::text))),
    CONSTRAINT buckets_versioning_status_check CHECK ((versioning_status = ANY (ARRAY['DISABLED'::text, 'ENABLED'::text, 'SUSPENDED'::text])))
);


ALTER TABLE storage.buckets OWNER TO supabase_storage_admin;

--
-- Name: COLUMN buckets.owner; Type: COMMENT; Schema: storage; Owner: supabase_storage_admin
--

COMMENT ON COLUMN storage.buckets.owner IS 'Field is deprecated, use owner_id instead';


--
-- Name: buckets_analytics; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.buckets_analytics (
    name text NOT NULL,
    type storage.buckettype DEFAULT 'ANALYTICS'::storage.buckettype NOT NULL,
    format text DEFAULT 'ICEBERG'::text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    deleted_at timestamp with time zone
);


ALTER TABLE storage.buckets_analytics OWNER TO supabase_storage_admin;

--
-- Name: buckets_vectors; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.buckets_vectors (
    id text NOT NULL,
    type storage.buckettype DEFAULT 'VECTOR'::storage.buckettype NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE storage.buckets_vectors OWNER TO supabase_storage_admin;

--
-- Name: migrations; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.migrations (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    hash character varying(40) NOT NULL,
    executed_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE storage.migrations OWNER TO supabase_storage_admin;

--
-- Name: objects; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.objects (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    bucket_id text,
    name text,
    owner uuid,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    last_accessed_at timestamp with time zone DEFAULT now(),
    metadata jsonb,
    path_tokens text[] GENERATED ALWAYS AS (string_to_array(name, '/'::text)) STORED,
    version text,
    owner_id text,
    user_metadata jsonb,
    archived_at timestamp with time zone,
    is_delete_marker boolean DEFAULT false NOT NULL,
    is_versioned boolean DEFAULT false NOT NULL
);


ALTER TABLE storage.objects OWNER TO supabase_storage_admin;

--
-- Name: COLUMN objects.owner; Type: COMMENT; Schema: storage; Owner: supabase_storage_admin
--

COMMENT ON COLUMN storage.objects.owner IS 'Field is deprecated, use owner_id instead';


--
-- Name: s3_multipart_uploads; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.s3_multipart_uploads (
    id text NOT NULL,
    in_progress_size bigint DEFAULT 0 NOT NULL,
    upload_signature text NOT NULL,
    bucket_id text NOT NULL,
    key text NOT NULL COLLATE pg_catalog."C",
    version text NOT NULL,
    owner_id text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    user_metadata jsonb,
    metadata jsonb
);


ALTER TABLE storage.s3_multipart_uploads OWNER TO supabase_storage_admin;

--
-- Name: s3_multipart_uploads_parts; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.s3_multipart_uploads_parts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    upload_id text NOT NULL,
    size bigint DEFAULT 0 NOT NULL,
    part_number integer NOT NULL,
    bucket_id text NOT NULL,
    key text NOT NULL COLLATE pg_catalog."C",
    etag text NOT NULL,
    owner_id text,
    version text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE storage.s3_multipart_uploads_parts OWNER TO supabase_storage_admin;

--
-- Name: vector_indexes; Type: TABLE; Schema: storage; Owner: supabase_storage_admin
--

CREATE TABLE storage.vector_indexes (
    id text DEFAULT gen_random_uuid() NOT NULL,
    name text NOT NULL COLLATE pg_catalog."C",
    bucket_id text NOT NULL,
    data_type text NOT NULL,
    dimension integer NOT NULL,
    distance_metric text NOT NULL,
    metadata_configuration jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE storage.vector_indexes OWNER TO supabase_storage_admin;

--
-- Name: refresh_tokens id; Type: DEFAULT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.refresh_tokens ALTER COLUMN id SET DEFAULT nextval('auth.refresh_tokens_id_seq'::regclass);


--
-- Name: recuperacion_contrasena id_recuperacion; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.recuperacion_contrasena ALTER COLUMN id_recuperacion SET DEFAULT nextval('public.recuperacion_contrasena_id_recuperacion_seq'::regclass);


--
-- Name: respaldo id_respaldo; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.respaldo ALTER COLUMN id_respaldo SET DEFAULT nextval('public.respaldo_id_respaldo_seq'::regclass);


--
-- Data for Name: audit_log_entries; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.audit_log_entries (instance_id, id, payload, created_at, ip_address) FROM stdin;
\.


--
-- Data for Name: custom_oauth_providers; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.custom_oauth_providers (id, provider_type, identifier, name, client_id, client_secret, acceptable_client_ids, scopes, pkce_enabled, attribute_mapping, authorization_params, enabled, email_optional, issuer, discovery_url, skip_nonce_check, cached_discovery, discovery_cached_at, authorization_url, token_url, userinfo_url, jwks_uri, created_at, updated_at, custom_claims_allowlist) FROM stdin;
\.


--
-- Data for Name: flow_state; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.flow_state (id, user_id, auth_code, code_challenge_method, code_challenge, provider_type, provider_access_token, provider_refresh_token, created_at, updated_at, authentication_method, auth_code_issued_at, invite_token, referrer, oauth_client_state_id, linking_target_id, email_optional) FROM stdin;
\.


--
-- Data for Name: identities; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.identities (provider_id, user_id, identity_data, provider, last_sign_in_at, created_at, updated_at, id) FROM stdin;
\.


--
-- Data for Name: instances; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.instances (id, uuid, raw_base_config, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: mfa_amr_claims; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.mfa_amr_claims (session_id, created_at, updated_at, authentication_method, id) FROM stdin;
\.


--
-- Data for Name: mfa_challenges; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.mfa_challenges (id, factor_id, created_at, verified_at, ip_address, otp_code, web_authn_session_data) FROM stdin;
\.


--
-- Data for Name: mfa_factors; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.mfa_factors (id, user_id, friendly_name, factor_type, status, created_at, updated_at, secret, phone, last_challenged_at, web_authn_credential, web_authn_aaguid, last_webauthn_challenge_data) FROM stdin;
\.


--
-- Data for Name: mfa_recovery_code_sets; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.mfa_recovery_code_sets (id, user_id, mfa_factor_id, failed_verification_count, verification_locked_until, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: mfa_recovery_codes; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.mfa_recovery_codes (id, mfa_recovery_code_set_id, code_hash, consumed_at, created_at) FROM stdin;
\.


--
-- Data for Name: oauth_authorizations; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.oauth_authorizations (id, authorization_id, client_id, user_id, redirect_uri, scope, state, resource, code_challenge, code_challenge_method, response_type, status, authorization_code, created_at, expires_at, approved_at, nonce) FROM stdin;
\.


--
-- Data for Name: oauth_client_states; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.oauth_client_states (id, provider_type, code_verifier, created_at) FROM stdin;
\.


--
-- Data for Name: oauth_clients; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.oauth_clients (id, client_secret_hash, registration_type, redirect_uris, grant_types, client_name, client_uri, logo_uri, created_at, updated_at, deleted_at, client_type, token_endpoint_auth_method) FROM stdin;
\.


--
-- Data for Name: oauth_consents; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.oauth_consents (id, user_id, client_id, scopes, granted_at, revoked_at) FROM stdin;
\.


--
-- Data for Name: one_time_tokens; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.one_time_tokens (id, user_id, token_type, token_hash, relates_to, created_at, updated_at, expires_at) FROM stdin;
\.


--
-- Data for Name: refresh_tokens; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.refresh_tokens (instance_id, id, token, user_id, revoked, created_at, updated_at, parent, session_id) FROM stdin;
\.


--
-- Data for Name: saml_providers; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.saml_providers (id, sso_provider_id, entity_id, metadata_xml, metadata_url, attribute_mapping, created_at, updated_at, name_id_format) FROM stdin;
\.


--
-- Data for Name: saml_relay_states; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.saml_relay_states (id, sso_provider_id, request_id, for_email, redirect_to, created_at, updated_at, flow_state_id) FROM stdin;
\.


--
-- Data for Name: schema_migrations; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.schema_migrations (version) FROM stdin;
20171026211738
20171026211808
20171026211834
20180103212743
20180108183307
20180119214651
20180125194653
00
20210710035447
20210722035447
20210730183235
20210909172000
20210927181326
20211122151130
20211124214934
20211202183645
20220114185221
20220114185340
20220224000811
20220323170000
20220429102000
20220531120530
20220614074223
20220811173540
20221003041349
20221003041400
20221011041400
20221020193600
20221021073300
20221021082433
20221027105023
20221114143122
20221114143410
20221125140132
20221208132122
20221215195500
20221215195800
20221215195900
20230116124310
20230116124412
20230131181311
20230322519590
20230402418590
20230411005111
20230508135423
20230523124323
20230818113222
20230914180801
20231027141322
20231114161723
20231117164230
20240115144230
20240214120130
20240306115329
20240314092811
20240427152123
20240612123726
20240729123726
20240802193726
20240806073726
20241009103726
20250717082212
20250731150234
20250804100000
20250901200500
20250903112500
20250904133000
20250925093508
20251007112900
20251104100000
20251111201300
20251201000000
20260115000000
20260121000000
20260219120000
20260302000000
20260625000000
20260821000000
20260821010000
20260824000000
20260824000001
20260831180000
\.


--
-- Data for Name: scim_tokens; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.scim_tokens (id, sso_provider_id, token_hash, prefix, created_at, expires_at, revoked_at, last_used_at) FROM stdin;
\.


--
-- Data for Name: scim_users; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.scim_users (id, sso_provider_id, user_id, resource, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.sessions (id, user_id, created_at, updated_at, factor_id, aal, not_after, refreshed_at, user_agent, ip, tag, oauth_client_id, refresh_token_hmac_key, refresh_token_counter, scopes) FROM stdin;
\.


--
-- Data for Name: sso_domains; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.sso_domains (id, sso_provider_id, domain, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: sso_providers; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.sso_providers (id, resource_id, created_at, updated_at, disabled) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.users (instance_id, id, aud, role, email, encrypted_password, email_confirmed_at, invited_at, confirmation_token, confirmation_sent_at, recovery_token, recovery_sent_at, email_change_token_new, email_change, email_change_sent_at, last_sign_in_at, raw_app_meta_data, raw_user_meta_data, is_super_admin, created_at, updated_at, phone, phone_confirmed_at, phone_change, phone_change_token, phone_change_sent_at, email_change_token_current, email_change_confirm_status, banned_until, reauthentication_token, reauthentication_sent_at, is_sso_user, deleted_at, is_anonymous) FROM stdin;
\.


--
-- Data for Name: webauthn_challenges; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.webauthn_challenges (id, user_id, challenge_type, session_data, created_at, expires_at) FROM stdin;
\.


--
-- Data for Name: webauthn_credentials; Type: TABLE DATA; Schema: auth; Owner: supabase_auth_admin
--

COPY auth.webauthn_credentials (id, user_id, credential_id, public_key, attestation_type, aaguid, sign_count, transports, backup_eligible, backed_up, friendly_name, created_at, updated_at, last_used_at) FROM stdin;
\.


--
-- Data for Name: bitacora_auditoria; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bitacora_auditoria (id_evento, id_usuario, id_tienda, entidad, accion, valor_anterior, valor_nuevo, resultado, ip_origen, fecha) FROM stdin;
1	1	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-02 23:44:53.829853+00
2	1	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-02 23:59:02.048244+00
3	1	\N	USUARIO	CREACION	\N	{"correo": "juan@vending.com", "estado": true, "id_rol": "4", "nombre": "Juan", "apellido": "Perez", "id_usuario": 2}	EXITOSO	::1	2026-10-03 07:24:23.177354+00
4	1	\N	USUARIO	EDICION	{"correo": "juan@vending.com", "estado": true, "id_rol": 4, "nombre": "Juan", "apellido": "Perez", "id_usuario": 2}	{"correo": "juan@vending.com", "estado": true, "id_rol": "4", "nombre": "Juan Carlos", "apellido": "Perez", "id_usuario": "2"}	EXITOSO	::1	2026-10-03 08:07:40.650448+00
5	1	\N	USUARIO	EDICION	{"correo": "juan@vending.com", "estado": true, "id_rol": 4, "nombre": "Juan Carlos", "apellido": "Perez", "id_usuario": 2}	{"correo": "juan@vending.com", "estado": true, "id_rol": "4", "nombre": "Juan Carlos", "apellido": "Perez", "id_usuario": "2"}	EXITOSO	::1	2026-10-03 08:09:25.546366+00
6	1	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-03 08:10:20.477779+00
7	2	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-03 08:14:02.398675+00
8	1	\N	USUARIO	CAMBIO_ESTADO	{"estado": true, "id_usuario": 2}	{"estado": false, "id_usuario": 2}	EXITOSO	::1	2026-10-03 08:26:14.757402+00
9	1	\N	USUARIO	CAMBIO_ESTADO	{"estado": false, "id_usuario": 2}	{"estado": true, "id_usuario": 2}	EXITOSO	::1	2026-10-03 08:26:20.485728+00
10	1	\N	USUARIO	CAMBIO_ESTADO	{"estado": true, "id_usuario": 2}	{"estado": false, "id_usuario": 2}	EXITOSO	::1	2026-10-03 08:26:28.705356+00
11	1	\N	USUARIO	CREACION	\N	{"correo": "pedro@vending.com", "estado": true, "id_rol": "4", "nombre": "Pedro", "apellido": "Gomez", "id_tienda": "2", "id_usuario": 3}	EXITOSO	::1	2026-10-03 09:02:35.130266+00
12	1	\N	USUARIO	EDICION	{"correo": "pedro@vending.com", "estado": true, "id_rol": 4, "nombre": "Pedro", "apellido": "Gomez", "id_usuario": 3}	{"correo": "pedro@vending.com", "estado": true, "id_rol": "4", "nombre": "Pedro", "apellido": "Gomez", "id_tienda": "3", "id_usuario": "3"}	EXITOSO	::1	2026-10-03 09:17:53.561405+00
13	1	\N	USUARIO	EDICION	{"correo": "pedro@vending.com", "estado": true, "id_rol": 4, "nombre": "Pedro", "apellido": "Gomez", "id_usuario": 3}	{"correo": "pedro@vending.com", "estado": true, "id_rol": "4", "nombre": "Pedro", "apellido": "Gomez", "id_tienda": "3", "id_usuario": "3"}	EXITOSO	::1	2026-10-03 09:18:40.389021+00
14	1	\N	USUARIO	EDICION	{"correo": "pedro@vending.com", "estado": true, "id_rol": 4, "nombre": "Pedro", "apellido": "Gomez", "id_usuario": 3}	{"correo": "pedro@vending.com", "estado": true, "id_rol": "4", "nombre": "Pedro", "apellido": "Gomez", "id_tienda": "1", "id_usuario": "3"}	EXITOSO	::1	2026-10-03 09:19:05.097354+00
15	1	\N	USUARIO	EDICION	{"correo": "pedro@vending.com", "estado": true, "id_rol": 4, "nombre": "Pedro", "apellido": "Gomez", "id_usuario": 3}	{"correo": "pedro@vending.com", "estado": true, "id_rol": "4", "nombre": "Pedro", "apellido": "Gomez", "id_tienda": "3", "id_usuario": "3"}	EXITOSO	::1	2026-10-03 09:24:11.012985+00
16	1	\N	USUARIO	EDICION	{"correo": "pedro@vending.com", "estado": true, "id_rol": 4, "nombre": "Pedro", "apellido": "Gomez", "id_usuario": 3}	{"correo": "pedro@vending.com", "estado": true, "id_rol": "4", "nombre": "Pedro", "apellido": "Gomez", "id_tienda": "3", "id_usuario": "3"}	EXITOSO	::1	2026-10-03 09:24:42.169376+00
17	1	\N	USUARIO	EDICION	{"correo": "pedro@vending.com", "estado": true, "id_rol": 4, "nombre": "Pedro", "apellido": "Gomez", "id_usuario": 3}	{"correo": "pedro@vending.com", "estado": true, "id_rol": "4", "nombre": "Pedro", "apellido": "Gomez", "id_tienda": "3", "id_usuario": "3"}	EXITOSO	::1	2026-10-03 09:25:04.765647+00
18	1	\N	USUARIO	CAMBIO_ESTADO	{"estado": false, "id_usuario": 2}	{"estado": true, "id_usuario": 2}	EXITOSO	::1	2026-10-03 09:25:37.015432+00
19	1	\N	USUARIO	EDICION	{"correo": "juan@vending.com", "estado": true, "id_rol": 4, "nombre": "Juan Carlos", "apellido": "Perez", "id_usuario": 2}	{"correo": "juan@vending.com", "estado": true, "id_rol": "4", "nombre": "Juan Carlos", "apellido": "Perez", "id_tienda": "3", "id_usuario": "2"}	EXITOSO	::1	2026-10-03 09:25:44.79451+00
20	1	\N	USUARIO	EDICION	{"correo": "pedro@vending.com", "estado": true, "id_rol": 4, "nombre": "Pedro", "apellido": "Gomez", "id_usuario": 3}	{"correo": "pedro@vending.com", "estado": true, "id_rol": "4", "nombre": "Pedro", "apellido": "Gomez", "id_tienda": "3", "id_usuario": "3"}	EXITOSO	::1	2026-10-03 09:30:03.321292+00
21	1	\N	USUARIO	EDICION	{"correo": "pedro@vending.com", "estado": true, "id_rol": 4, "nombre": "Pedro", "apellido": "Gomez", "id_usuario": 3}	{"correo": "pedro@vending.com", "estado": true, "id_rol": "4", "nombre": "Pedro", "apellido": "Gomez", "id_tienda": "2", "id_usuario": "3"}	EXITOSO	::1	2026-10-03 09:44:21.699674+00
22	1	\N	USUARIO	EDICION	{"correo": "pedro@vending.com", "estado": true, "id_rol": 4, "nombre": "Pedro", "apellido": "Gomez", "id_usuario": 3}	{"correo": "pedro@vending.com", "estado": true, "id_rol": "4", "nombre": "Pedro", "apellido": "Gomez", "id_tienda": "2", "id_usuario": "3"}	EXITOSO	::1	2026-10-03 10:39:28.076089+00
23	1	\N	USUARIO	EDICION	{"correo": "pedro@vending.com", "estado": true, "id_rol": 4, "nombre": "Pedro", "apellido": "Gomez", "id_usuario": 3}	{"correo": "pedro@vending.com", "estado": true, "id_rol": "4", "nombre": "Pedro", "apellido": "Gomez", "id_tienda": "2", "id_usuario": "3"}	EXITOSO	::1	2026-10-03 10:41:02.352419+00
24	1	\N	USUARIO	EDICION	{"correo": "pedro@vending.com", "estado": true, "id_rol": 4, "nombre": "Pedro", "apellido": "Gomez", "id_usuario": 3}	{"correo": "pedro@vending.com", "estado": true, "id_rol": "4", "nombre": "Pedro", "apellido": "Gomez", "id_tienda": "2", "id_usuario": "3"}	EXITOSO	::1	2026-10-03 10:42:08.015623+00
25	1	\N	USUARIO	EDICION	{"correo": "pedro@vending.com", "estado": true, "id_rol": 4, "nombre": "Pedro", "apellido": "Gomez", "id_usuario": 3}	{"correo": "pedro@vending.com", "estado": true, "id_rol": "4", "nombre": "Pedro", "apellido": "Gomez", "id_tienda": "2", "id_usuario": "3"}	EXITOSO	::1	2026-10-03 10:56:28.681108+00
26	1	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-03 16:54:20.740816+00
27	1	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-03 18:28:57.211571+00
28	3	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-05 16:49:30.767356+00
29	1	\N	USUARIO	CREACION	\N	{"correo": "isaacalemanb@gmail.com", "estado": true, "id_rol": "1", "nombre": "Isaac", "apellido": "Aleman", "id_tienda": "1", "id_usuario": 4}	EXITOSO	::1	2026-10-05 17:42:17.521411+00
30	1	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-05 17:42:45.385243+00
31	4	\N	USUARIO	RESTABLECIMIENTO_CONTRASENA	\N	\N	EXITOSO	::1	2026-10-05 19:17:05.935781+00
32	4	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-05 20:04:42.995595+00
33	4	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-05 22:00:00.627361+00
34	4	\N	USUARIO	RESTABLECIMIENTO_CONTRASENA	\N	\N	EXITOSO	::1	2026-10-05 22:03:36.852139+00
35	4	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-05 22:09:23.589512+00
36	4	\N	USUARIO	RESTABLECIMIENTO_CONTRASENA	\N	\N	EXITOSO	::1	2026-10-05 22:10:16.020346+00
37	4	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-05 22:16:00.836909+00
38	4	\N	USUARIO	RESTABLECIMIENTO_CONTRASENA	\N	\N	EXITOSO	::1	2026-10-05 22:16:40.077883+00
39	1	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-06 01:39:08.461634+00
40	3	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-06 02:46:09.957545+00
41	1	\N	USUARIO	CREACION	\N	{"correo": "raul@vending.com", "estado": true, "id_rol": "4", "nombre": "Raúl", "apellido": "Pérez", "id_tienda": "1", "id_usuario": 5}	EXITOSO	::1	2026-10-06 06:40:15.556841+00
42	1	3	MAQUINA	CREAR	\N	{"tipo": "Bebidas", "codigo": "ZZ-TEST HU17 001", "estado": true, "modelo": "M1", "nombre": "Maquina de prueba", "id_tienda": 3, "ubicacion": "Entrada", "id_maquina": 1, "observaciones": "temporal", "tienda_activa": true, "tienda_nombre": "UltraLag", "fecha_registro": "2026-10-06 20:14:30.382519+00"}	EXITOSO	127.0.0.1	2026-10-06 20:14:30.382519+00
43	1	1	MAQUINA	CREAR	\N	{"tipo": "Snacks", "codigo": "ZZ-TEST HU17 002", "estado": false, "modelo": "M2", "nombre": "Prueba inactiva", "id_tienda": 1, "ubicacion": "Pasillo", "id_maquina": 2, "observaciones": "temporal 2", "tienda_activa": true, "tienda_nombre": "Ultrapark 1", "fecha_registro": "2026-10-06 22:00:00.528377+00"}	EXITOSO	127.0.0.1	2026-10-06 22:00:00.528377+00
44	1	2	MAQUINA	EDITAR	{"tipo": "Snacks", "codigo": "ZZ-TEST HU17 002", "estado": false, "modelo": "M2", "nombre": "Prueba inactiva", "id_tienda": 1, "ubicacion": "Pasillo", "id_maquina": 2, "observaciones": "temporal 2", "tienda_activa": true, "tienda_nombre": "Ultrapark 1", "fecha_registro": "2026-10-06 22:00:00.528377+00"}	{"tipo": "Snacks", "codigo": "ZZ-TEST HU17 002", "estado": false, "modelo": "M2b", "nombre": "Prueba inactiva editada", "id_tienda": 2, "ubicacion": "Entrada", "id_maquina": 2, "observaciones": "editada", "tienda_activa": true, "tienda_nombre": "Ultrapark 2", "fecha_registro": "2026-10-06 22:00:00.528377+00"}	EXITOSO	127.0.0.1	2026-10-06 22:00:02.959818+00
45	1	3	MAQUINA	EDITAR	{"tipo": "Snacks", "codigo": "ZZ-TEST HU17 002", "estado": false, "modelo": "M2b", "nombre": "Prueba inactiva editada", "id_tienda": 2, "ubicacion": "Entrada", "id_maquina": 2, "observaciones": "editada", "tienda_activa": true, "tienda_nombre": "Ultrapark 2", "fecha_registro": "2026-10-06 22:00:00.528377+00"}	{"tipo": "Snacks", "codigo": "ZZ-TEST HU17 002", "estado": false, "modelo": "M2b", "nombre": "Prueba inactiva editada", "id_tienda": 3, "ubicacion": "Entrada", "id_maquina": 2, "observaciones": "editada", "tienda_activa": true, "tienda_nombre": "UltraLag", "fecha_registro": "2026-10-06 22:00:00.528377+00"}	EXITOSO	::1	2026-10-06 22:13:50.892341+00
46	4	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-06 22:24:07.867137+00
47	3	\N	USUARIO	CIERRE_SESION	\N	\N	EXITOSO	::1	2026-10-06 22:24:41.472727+00
48	1	2	RELACION_ABASTECIMIENTO	CREAR	\N	{"estado": true, "id_destino": 3, "id_relacion": 1, "origen_tipo": "TIENDA", "destino_tipo": "UBICACION", "observaciones": "UP2 abastece a UltraLag", "origen_activa": true, "origen_nombre": "Ultrapark 2", "tipo_relacion": "Tienda → Tienda", "destino_activo": true, "destino_nombre": "UltraLag", "fecha_registro": "2026-10-06 23:55:57.195365+00", "maquina_nombre": null, "id_tienda_origen": 2, "id_tienda_destino": 3, "id_maquina_destino": null}	EXITOSO	127.0.0.1	2026-10-06 23:55:57.195365+00
49	1	2	RELACION_ABASTECIMIENTO	EDITAR	{"estado": true, "id_destino": 3, "id_relacion": 1, "origen_tipo": "TIENDA", "destino_tipo": "UBICACION", "observaciones": "UP2 abastece a UltraLag", "origen_activa": true, "origen_nombre": "Ultrapark 2", "tipo_relacion": "Tienda → Tienda", "destino_activo": true, "destino_nombre": "UltraLag", "fecha_registro": "2026-10-06 23:55:57.195365+00", "maquina_nombre": null, "id_tienda_origen": 2, "id_tienda_destino": 3, "id_maquina_destino": null}	{"estado": true, "id_destino": 3, "id_relacion": 1, "origen_tipo": "TIENDA", "destino_tipo": "UBICACION", "observaciones": "UP2 abastece a UltraLag y sus maquinas", "origen_activa": true, "origen_nombre": "Ultrapark 2", "tipo_relacion": "Tienda → Tienda", "destino_activo": true, "destino_nombre": "UltraLag", "fecha_registro": "2026-10-06 23:55:57.195365+00", "maquina_nombre": null, "id_tienda_origen": 2, "id_tienda_destino": 3, "id_maquina_destino": null}	EXITOSO	127.0.0.1	2026-10-06 23:56:02.582584+00
50	1	2	RELACION_ABASTECIMIENTO	INACTIVAR	{"estado": true, "id_destino": 3, "id_relacion": 1, "origen_tipo": "TIENDA", "destino_tipo": "UBICACION", "observaciones": "UP2 abastece a UltraLag y sus maquinas", "origen_activa": true, "origen_nombre": "Ultrapark 2", "tipo_relacion": "Tienda → Tienda", "destino_activo": true, "destino_nombre": "UltraLag", "fecha_registro": "2026-10-06 23:55:57.195365+00", "maquina_nombre": null, "id_tienda_origen": 2, "id_tienda_destino": 3, "id_maquina_destino": null}	{"estado": false, "id_destino": 3, "id_relacion": 1, "origen_tipo": "TIENDA", "destino_tipo": "UBICACION", "observaciones": "UP2 abastece a UltraLag y sus maquinas", "origen_activa": true, "origen_nombre": "Ultrapark 2", "tipo_relacion": "Tienda → Tienda", "destino_activo": true, "destino_nombre": "UltraLag", "fecha_registro": "2026-10-06 23:55:57.195365+00", "maquina_nombre": null, "id_tienda_origen": 2, "id_tienda_destino": 3, "id_maquina_destino": null}	EXITOSO	127.0.0.1	2026-10-06 23:56:04.665444+00
51	1	2	RELACION_ABASTECIMIENTO	ACTIVAR	{"estado": false, "id_destino": 3, "id_relacion": 1, "origen_tipo": "TIENDA", "destino_tipo": "UBICACION", "observaciones": "UP2 abastece a UltraLag y sus maquinas", "origen_activa": true, "origen_nombre": "Ultrapark 2", "tipo_relacion": "Tienda → Tienda", "destino_activo": true, "destino_nombre": "UltraLag", "fecha_registro": "2026-10-06 23:55:57.195365+00", "maquina_nombre": null, "id_tienda_origen": 2, "id_tienda_destino": 3, "id_maquina_destino": null}	{"estado": true, "id_destino": 3, "id_relacion": 1, "origen_tipo": "TIENDA", "destino_tipo": "UBICACION", "observaciones": "UP2 abastece a UltraLag y sus maquinas", "origen_activa": true, "origen_nombre": "Ultrapark 2", "tipo_relacion": "Tienda → Tienda", "destino_activo": true, "destino_nombre": "UltraLag", "fecha_registro": "2026-10-06 23:55:57.195365+00", "maquina_nombre": null, "id_tienda_origen": 2, "id_tienda_destino": 3, "id_maquina_destino": null}	EXITOSO	127.0.0.1	2026-10-06 23:56:09.587672+00
53	4	\N	RESPALDO	REGISTRO_RESPALDO	\N	{"tipo": "MANUAL", "estado": "EXITOSO", "cobertura": "Base de datos y información crítica", "id_respaldo": 1}	EXITOSO	::1	2026-10-07 06:04:37.226059+00
\.


--
-- Data for Name: categoria; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.categoria (id_categoria, nombre, descripcion, estado) FROM stdin;
1	Bebidas	Productos de bebidas	t
2	Confites y Chocolates	Confites, chocolates y productos relacionados	t
3	Galletas	Productos de galletería	t
4	Snacks	Snacks y productos similares	t
5	Panadería	Productos de panadería	t
6	Pastillas y Cuidado personal	Productos de farmacia y cuidado personal	t
7	Helados	Helados y productos congelados relacionados	t
8	Yogur y lácteos	Yogures y productos lácteos	t
9	Comida preparada	Alimentos preparados	t
10	Maní y frutos secos	Maní, semillas y frutos secos	t
11	Item Hierarchy 1	Productos pendientes de asignar a una categoría definitiva	t
\.


--
-- Data for Name: impuesto; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.impuesto (id_impuesto, nombre, porcentaje, descripcion, estado) FROM stdin;
1	IVA 13%	13.00	Impuesto general aplicado al producto	t
6	IVA 1%	1.00	Impuesto del 1% aplicado según el producto	t
7	IVA 2%	2.00	Impuesto del 2% aplicado según el producto	t
\.


--
-- Data for Name: maquina; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.maquina (id_maquina, id_tienda, codigo, nombre, ubicacion, modelo, tipo, estado, observaciones, fecha_registro) FROM stdin;
1	3	ZZ-TEST HU17 001	Maquina de prueba	Entrada	M1	Bebidas	t	temporal	2026-10-06 20:14:30.382519+00
2	3	ZZ-TEST HU17 002	Prueba inactiva editada	Entrada	M2b	Snacks	f	editada	2026-10-06 22:00:00.528377+00
\.


--
-- Data for Name: maquina_producto; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.maquina_producto (id_maquina_producto, id_maquina, id_producto, canal, capacidad, estado, fecha_actualizacion) FROM stdin;
\.


--
-- Data for Name: margen_ganancia; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.margen_ganancia (id_margen, nombre, porcentaje, descripcion, estado) FROM stdin;
1	Margen 1.4	40.00	Factor de precio 1.4	t
2	Margen 1.5	50.00	Factor de precio 1.5	t
3	Margen 1.6	60.00	Factor de precio 1.6	t
4	Margen 1.7	70.00	Factor de precio 1.7	t
\.


--
-- Data for Name: precio_tienda; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.precio_tienda (id_precio, id_producto, id_tienda, id_margen, costo, precio_con_impuesto, precio_venta, fecha_inicio, fecha_fin, estado) FROM stdin;
\.


--
-- Data for Name: producto; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.producto (id_producto, id_categoria, id_impuesto, codigo_articulo_retail, codigo_barras, nombre, descripcion, estado) FROM stdin;
1	11	1	np-topup	\N	Nayax Prepaid Top-up	\N	t
2	11	1	gc-topup	\N	Gift Card Top-up	\N	t
3	11	1	1000	\N	General item	\N	t
4	11	1	\N	\N	Mani Salado	Código Nova duplicado en el origen: CR12; pendiente de revisión	t
5	1	1	CR251	\N	DR PEPPER ORIGINAL LATA 355ML	\N	t
6	11	1	\N	\N	Chirulitos	Código Nova en conflicto en el origen: CR02	t
7	11	1	Mani con Limon	\N	Mani con limon	\N	t
8	1	1	CR01	\N	Fanta uva 600 ml	\N	t
9	1	1	CR02	\N	Fanta Naranja 600 ml	\N	t
10	1	1	CR03	\N	Canada Dry 600 ml	\N	t
11	1	1	CR04	\N	Coca Cola Zero 600ml	\N	t
12	1	1	CR05	\N	Fanta Kolita 600ml	\N	t
13	1	1	CR06	\N	Hi-c Te de limon 400ml	\N	t
14	1	1	CR07	\N	Fuze tea te negro melocoton	\N	t
15	1	1	CR08	\N	Fuze tea te verde mango manzanilla 500ml	\N	t
16	1	1	CR09	\N	Fuze tea te verde fresa y aloe 500ml	\N	t
17	1	1	CR10	\N	Fuze tea te frio limon 500ml	\N	t
18	1	1	CR11	\N	Big Cola 500ml	\N	t
19	1	1	\N	\N	Aloe Vera original	Código Nova duplicado en el origen: CR12; pendiente de revisión	t
20	1	1	CR13	\N	De la granja Bebida de naranja	\N	t
21	1	1	CR14	\N	RAPTOR BOTELLA 600ml	\N	t
22	1	1	CR15	\N	Gatorade Mora	\N	t
23	1	1	CR16	\N	Gatorade Fruit Punch	\N	t
24	1	1	CR17	\N	Gatorade Naranja 600ml	\N	t
25	1	1	CR18	\N	Powerade Naranja 600ml	\N	t
26	1	1	CR19	\N	Monster verde 473ml	\N	t
27	1	1	CR20	\N	Monster Zero 473ml	\N	t
28	1	1	CR21	\N	Monster mango loco 473ml	\N	t
29	1	1	CR22	\N	Fresca 355ml	\N	t
30	1	1	CR23	\N	Fanta naranja 355ml	\N	t
31	1	1	CR24	\N	fanta uva 355 ml	\N	t
32	1	1	CR25	\N	Coca-cola zero 355ml	\N	t
33	1	1	CR26	\N	Fanta Kolita 355ml	\N	t
34	1	1	CR27	\N	Canada dry 355ml	\N	t
35	1	1	CR28	\N	Hi-c Melocoton 250ml	\N	t
36	1	1	CR29	\N	Hi-c pera 250ml	\N	t
37	1	1	CR30	\N	Hi-c Manzana 250ml	\N	t
38	1	1	CR31	\N	(DP) Leche semidescremada 250ml	\N	t
39	1	1	CR32	\N	(DP) Leche proteina Vainilla 250ml	\N	t
40	1	1	CR33	\N	(DP) Leche de proteina choco almendras 250ml	\N	t
41	1	1	CR34	\N	Hi-c Limon 330ml	\N	t
42	1	1	CR35	\N	Coca Cola original 600 ml	\N	t
43	7	1	CR36	\N	Helado Vainilla con Chips de Chocolate	\N	t
44	7	1	CR37	\N	Helado Trits Vainilla	\N	t
45	7	1	CR38	\N	Trits Naranja Holandesa	\N	t
46	7	1	CR40	\N	Mini sandwich Vainilla	\N	t
47	7	1	CR39	\N	Mini Sandwich Chocolate	\N	t
48	7	1	CR41	\N	Helado Sundae Churchill	\N	t
49	7	1	CR42	\N	Helado Sundae Caramelo	\N	t
50	7	1	CR44	\N	Alaska Paleta Alaska Frutas Mandarina	\N	t
51	7	1	CR43	\N	Helado Sundae Chocolate	\N	t
52	7	1	CR46	\N	Helado MMMIO	\N	t
53	7	1	CR45	\N	Paleta Alaska Frambuesa Limón	\N	t
54	7	1	CR48	\N	Parejita Chocolate	\N	t
55	7	1	CR47	\N	Paleta Chocolate Cook & Cream	\N	t
56	7	1	CR50	\N	Cremoleta Fresa	\N	t
57	7	1	CR51	\N	Chocoleta	\N	t
58	7	1	CR49	\N	Cremoleta Limón	\N	t
59	7	1	CR52	\N	Super Cono	\N	t
60	7	1	CR53	\N	Cremoleta Naranja	\N	t
61	7	1	CR54	\N	Krunchy Torta Chilena	\N	t
62	7	1	CR56	\N	Cono Deleite Caramelo	\N	t
63	7	1	CR57	\N	Choco Snack	\N	t
64	7	1	CR55	\N	Krunchy Krips	\N	t
65	11	1	CR58	\N	Chicharritos Limon	\N	t
66	11	1	CR59	\N	Chicharritos BBQ	\N	t
67	11	1	CR60	\N	Patacones Rumba	\N	t
68	11	1	CR61	\N	Tosty Papiolas Deluxe	\N	t
69	11	1	CR62	\N	Tosty Pizzerolas	\N	t
70	11	1	CR63	\N	Tosty Bolitas de queso	\N	t
71	11	1	CR64	\N	Tosty Tronaditas	\N	t
72	11	1	CR65	\N	Tosty Quesitos	\N	t
73	11	1	CR66	\N	Tosty Chirulitos	\N	t
74	11	1	CR67	\N	Tosty Papiolas BBQ	\N	t
75	11	1	CR68	\N	Picaronas Queso Nacho	\N	t
76	11	1	CR69	\N	Picaronas Queso Nacho Picante	\N	t
77	11	1	CR70	\N	Picaritas	\N	t
78	11	1	CR71	\N	Pro Yuquitas Crema y cebolla	\N	t
79	11	1	CR72	\N	Pro Yuquitas Rancheras	\N	t
80	11	1	CR73	\N	Pro Toreaditos limon	\N	t
81	11	1	CR74	\N	Pro Toraditos BBQ	\N	t
82	11	1	CR75	\N	Pro Toreaditos Nacho chili	\N	t
83	1	1	CR76	\N	Coca Cola 355ml	\N	t
84	1	1	CR77	\N	RAPTOR XTRACTO TE VERDE GUARANA 473ML	\N	t
85	7	1	CR78	\N	Queque con Helado	\N	t
86	1	1	CR79	\N	Te Melocoton 500ml Dos Pinos	\N	t
87	1	1	CR80	\N	Te Negro 500ml Dos Pinos	\N	t
88	1	1	CR81	\N	Te blanco 500ml Dos Pinos	\N	t
89	1	1	CR82	\N	Te Verde Cero 500ml Dos Pinos	\N	t
90	1	1	CR83	\N	Cotuba Guarana 600ml	\N	t
91	1	1	CR84	\N	Cotuba Lata	\N	t
92	1	1	CR85	\N	Cotuba lata Zero	\N	t
93	8	1	CR86	\N	Dos Pinos Yogurt Arandanos 250ml	\N	t
94	8	1	CR87	\N	Dos Pinos Yogurt Melocoton 200ml	\N	t
95	8	1	CR88	\N	Coronado Yogurt Fresa 200ml	\N	t
96	8	1	CR89	\N	Coronado Yogurt Mora	\N	t
97	8	1	CR90	\N	Toppong Deligurt Sabor Original con arandanos	\N	t
98	8	1	CR91	\N	Topping  Deligurt Sabor fresa con chocolate	\N	t
99	8	1	CR92	\N	Topping Deligurt sabor Coco con almendras	\N	t
100	1	1	CR93	\N	Dos pinos Te blanco 250ml	\N	t
101	1	1	CR94	\N	Alma Sparkling Limon-Toronja	\N	t
102	1	1	CR95	\N	Alma Sparkling Fresa-Kiwi	\N	t
103	1	1	CR96	\N	Jugo de Naranja Dos Pinos 500ml	\N	t
104	1	1	CR97	\N	Jugo de Naranja Dos Pinos 250ml	\N	t
105	1	1	CR98	\N	Jugo de Naranja 100% 250ml	\N	t
106	1	1	CR99	\N	Dos Pinos Jugo de manzana 100% 250ml	\N	t
107	1	1	CR100	\N	Dos Pinos Leche c/Avena 250ml	\N	t
108	1	1	CR101	\N	Dos Pinos Fresco leche fresa 250ml	\N	t
109	1	1	CR102	\N	Dos Pinos Fresco Leche Vainilla 250ml	\N	t
110	1	1	CR103	\N	Dos Pinos Fresco leche Chocolate 250ml	\N	t
111	5	1	CR105	\N	Bizcochos Caseros con Jalea de Piña	\N	t
112	5	1	CR106	\N	Zarcereña Palmeritas	\N	t
113	5	1	CR107	\N	Arrollados dulce de leche	\N	t
114	5	1	CR108	\N	Gatos de dulce de Leche	\N	t
115	5	1	CR109	\N	Palitos dulces Zarcereña	\N	t
116	11	1	CR110	\N	Taqueritos Dragon Azul	\N	t
117	11	1	CR111	\N	Taqueritos Dragon Rojo	\N	t
118	11	1	CR112	\N	Taqueritos Chile Toreado	\N	t
119	11	1	CR113	\N	Taqueritos Queso fusion	\N	t
120	11	1	CR114	\N	Ranchitas Nacho guacamole	\N	t
121	11	1	CR115	\N	Ranchitas Nacho Pizza	\N	t
122	4	1	CR116	\N	Ziba`s Miel Mostaza	\N	t
123	11	1	CR117	\N	Cosecha Dorada Palitos Integrales Ajonjolì	\N	t
124	11	1	CR118	\N	Cosecha Dorada Palitos Integrales Dulces	\N	t
125	11	1	CR119	\N	Cosecha Dorada Palitos integrales de pizza	\N	t
126	11	1	CR120	\N	Cosecha Dorada Palitos integrales de queso	\N	t
127	11	1	CR121	\N	Cosecha Dorada Palitos integrales con aceite	\N	t
128	11	1	CR122	\N	Rosquillas Maiz y Queso Nutri Snacks 30g	\N	t
129	11	1	CR123	\N	Nutri Snack Palitos Ajonjoli	\N	t
130	11	1	CR124	\N	Rosquillas queso Cheddar	\N	t
131	11	1	CR125	\N	Nutri snack Bisco lite	\N	t
132	11	1	CR126	\N	Nutri Snack Bisco Lite Picante	\N	t
133	11	1	CR127	\N	Nutri Snack Palitos Queso cheddar	\N	t
134	11	1	CR128	\N	Bizcocho Palmareño Light	\N	t
135	11	1	CR129	\N	Bizcocho Palmareño	\N	t
136	11	1	CR130	\N	Piña Deshidratada chocolate	\N	t
137	11	1	CR131	\N	Piña Deshidratada	\N	t
138	11	1	CR132	\N	Cheetos Crunchy	\N	t
139	11	1	CR133	\N	Nutri Snack Palitos de oliva y amapola	\N	t
140	11	1	CR134	\N	Soldanza Platanitos limon	\N	t
141	4	1	CR135	\N	Soldanza Platanitos Saladitos lightly	\N	t
142	4	1	CR136	\N	Soldanza RIpe plantain chips	\N	t
143	11	1	CR137	\N	Soldanza Maduritos	\N	t
144	4	1	CR138	\N	Soldanza Yuquitas Originales.	\N	t
145	4	1	CR139	\N	Soldanza Yuquitas Crema y cebolla	\N	t
146	11	1	CR140	\N	Lays BBQ	\N	t
147	11	1	CR141	\N	Lays Crema y Cebolla 28.3g	\N	t
148	11	1	CR142	\N	Lays Classic	\N	t
149	3	1	CR178	\N	Chiky Chocolate	\N	t
150	3	1	CR179	\N	Chiky Fresa	\N	t
151	3	1	CR180	\N	Chiky Vainilla	\N	t
152	3	1	CR181	\N	Galletas Trits Naranja Holandesa	\N	t
153	3	1	CR182	\N	Trits Pie de Limon	\N	t
154	3	1	CR183	\N	Recreo limon	\N	t
155	3	1	CR184	\N	Recreo Vainilla	\N	t
156	3	1	CR185	\N	Festival Naranja sorbeto	\N	t
157	3	1	CR186	\N	Festival Chocolate wafer	\N	t
158	3	1	CR187	\N	POZUELO CANASTA CHOCOLATE 23G	\N	t
159	3	1	CR188	\N	POZUELO CANASTA GUAYABA 21.6G	\N	t
160	3	1	CR189	\N	Merendina Chocolate	\N	t
161	3	1	CR190	\N	Merendina Vainilla	\N	t
162	3	1	CR191	\N	Yipy	\N	t
163	3	1	\N	\N	Galleta Yipy	Código Nova duplicado en el origen: CR149; pendiente de revisión	t
164	11	1	CR143	\N	Zambos Platanitos Maduritos	\N	t
165	11	1	CR144	\N	ZAMBOS PLATANO PICOSITAS 36G	\N	t
166	11	1	CR145	\N	Del Rancho Chicharrones	\N	t
167	11	1	CR146	\N	Doritos Spicy Sweet Chili	\N	t
168	11	1	CR147	\N	Lay´s Flamin´hot	\N	t
169	11	1	CR148	\N	Doritos Flamin´hot	\N	t
257	1	1	CR249	\N	Sparkling water Lemon	\N	t
170	11	1	\N	\N	Doritos queso acelerado	Código Nova duplicado en el origen: CR149; pendiente de revisión	t
171	6	1	CR150	\N	Antigripal Gex Noche	\N	t
172	6	1	CR151	\N	Antigripal Gex Día	\N	t
173	6	1	CR152	\N	Dorival Capsulas	\N	t
174	6	1	CR153	\N	Panadol Gripe multi-sintomas	\N	t
175	6	1	CR154	\N	Panadol gripe Día	\N	t
176	11	1	CR155	\N	Cheez-it	\N	t
177	11	1	CR156	\N	Cheez-it Snap`d	\N	t
178	2	1	CR157	\N	Rice krispies treats	\N	t
179	11	1	CR158	\N	Atun Sardimar Light	\N	t
180	4	1	CR159	\N	Soldanza Banana chips	\N	t
181	5	1	CR160	\N	Zarcereña Roscas de queso	\N	t
182	5	1	CR161	\N	Bizcocho de jalea guayaba	\N	t
183	5	1	CR162	\N	Bizcochos casero 100gr	\N	t
184	11	1	CR163	\N	Empanadas de Chiverre	\N	t
185	5	1	CR164	\N	Empanadas de dulce de leche	\N	t
186	5	1	CR165	\N	Empanadas de Piña	\N	t
187	10	1	CR166	\N	Pro Manì Horneado	\N	t
188	10	1	CR167	\N	Pro manì con Chocolate	\N	t
189	10	1	CR168	\N	Pro Mani Garapiñado	\N	t
190	10	1	CR169	\N	Pro mani Salado	\N	t
191	10	1	CR170	\N	Pro Mani salado con Pasas	\N	t
192	10	1	CR171	\N	Pro mani chile	\N	t
193	10	1	CR172	\N	Pro Mani limon y sal	\N	t
194	2	1	CR173	\N	Chocolate MilkyWay	\N	t
195	6	1	CR174	\N	Cepillo de dientes Colgate	\N	t
196	6	1	CR175	\N	Pasta de dientes Colgate pequeña	\N	t
197	7	1	CR176	\N	Helado de Vainilla 1/4	\N	t
198	7	1	CR177	\N	Helado Combinado Fresa limon	\N	t
199	2	1	CR192	\N	M&M Minis	\N	t
200	2	1	CR193	\N	Hubba Bubba Bubble tapo	\N	t
201	11	1	CR194	\N	Pringles Cheddar cheese 40g	\N	t
202	11	1	CR195	\N	PRINGLES ORIGINAL 40G	\N	t
203	11	1	CR196	\N	Pringles BBQ 40g	\N	t
204	11	1	CR197	\N	PRINGLES CREMA Y CEBOLLA 40G	\N	t
205	3	1	CR198	\N	Mr. Brownie	\N	t
206	2	1	CR199	\N	Pure organic Fruit bar Pineapple Passion fruit	\N	t
207	2	1	CR200	\N	Pure organic fruit bar Strawberry banana	\N	t
208	2	1	CR201	\N	Airheads White mystery	\N	t
209	2	1	CR202	\N	Airheads Blue Raspberry	\N	t
210	2	1	CR203	\N	Airheads Grape	\N	t
211	2	1	CR2024	\N	Airheads Watermelon	\N	t
212	2	1	CR204	\N	Airheads Orange	\N	t
213	2	1	CR205	\N	Airheads Cherry	\N	t
214	2	1	CR206	\N	TRIDENT YERBABUENA 18UND	\N	t
215	2	1	CR207	\N	Trident Sandia pack	\N	t
216	2	1	CR208	\N	Trident Yerbabuena slap	\N	t
217	2	1	CR209	\N	Trident Mora Azul slap	\N	t
218	4	1	CR210	\N	Soldanza Yuquitas Chipotle	\N	t
219	1	1	CR211	\N	Briko Manzana 250ml	\N	t
220	1	1	CR212	\N	Briko Frutas 250ml	\N	t
221	1	1	CR213	\N	Briko Uva 250ml	\N	t
222	1	1	CR214	\N	Dos pinos nectar Melocoton 200ml	\N	t
223	1	1	CR215	\N	Dos Pinos Nectar de Pera 200ml	\N	t
224	1	1	CR216	\N	Dos pinos Nectar Manzana 200ml	\N	t
225	2	1	CR217	\N	Gomitas trolli Kiss	\N	t
226	3	1	CR218	\N	Artesanas Originales 100gr	\N	t
227	8	1	CR219	\N	Deligurt topping Tapita	\N	t
228	1	1	CR220	\N	Dos Pinos Té Melocotón 250ml	\N	t
229	1	1	CR221	\N	Rompope 250ml	\N	t
230	3	1	CR222	\N	Coca Cola cherry	\N	t
231	1	1	CR223	\N	Coca Cola sin Azucar lata	\N	t
232	1	1	CR224	\N	Sprite Lata	\N	t
233	1	1	CR225	\N	Fresca Lata	\N	t
234	1	1	CR226	\N	Coca Cola Lata	\N	t
235	1	1	CR227	\N	Fanta Naranja Lata	\N	t
236	1	1	CR228	\N	Dos Pinos Te Rojo 500ml	\N	t
237	11	1	CR229	\N	Issima Sopa de pollo con chile	\N	t
238	11	1	CR230	\N	Issima Sopa sabor pollo	\N	t
239	11	1	CR231	\N	Issima Sopa de camaron picante	\N	t
240	3	1	CR232	\N	Artesanas Naranja 100gr	\N	t
241	3	1	CR233	\N	Artesanas Coco 100gr	\N	t
242	2	1	CR234	\N	Chocolates mini size	\N	t
243	4	1	CR235	\N	Ziba`s Miel Mostaza 40gr	\N	t
244	1	1	CR236	\N	Colcafe Cappuccino	\N	t
245	2	1	CR237	\N	Rice Krispies Original	\N	t
246	1	1	CR238	\N	Fresca 600ml	\N	t
247	2	1	CR239	\N	Gomitas Crismelos	\N	t
248	1	1	CR240	\N	Coca Cola Vainilla Lata	\N	t
249	2	1	CR241	\N	Push Pop sabores	\N	t
250	11	1	CR242	\N	Piroiline	\N	t
251	11	1	CR243	\N	Snack palitos dulces	\N	t
252	11	1	CR244	\N	Snack Bizcochos de maiz	\N	t
253	11	1	CR245	\N	Snack Palitos de queso	\N	t
254	1	1	CR246	\N	VK Energy Drink	\N	t
255	1	1	CR247	\N	Sparkling water orange	\N	t
256	1	1	CR248	\N	Sparkling water Lime	\N	t
258	1	1	CR250	\N	Fuze tea zero Te verde	\N	t
259	3	1	CR252	\N	Galleta Taifelds Avena Nuez	\N	t
260	1	1	CR253	\N	Pepsi Cola 355ml	\N	t
261	10	1	CR254	\N	Maní pro miel de abeja 80gr	\N	t
262	1	1	CR255	\N	Jet 600ml	\N	t
263	1	1	CR256	\N	Hi-c Melocotón 400ml	\N	t
264	8	1	CR257	\N	Topping deligur Fresa Granola	\N	t
265	8	1	CR258	\N	Topping Guayabita yogurt	\N	t
266	1	1	CR259	\N	Pepsi 600ml	\N	t
267	1	1	CR260	\N	Alma sparkling Pitahaya yerbabuena	\N	t
268	7	1	CR261	\N	Helados condorito agua	\N	t
269	7	1	CR262	\N	Helados condorito leche	\N	t
270	7	1	CR263	\N	Helados condorito choco	\N	t
271	2	1	CR264	\N	RING POP	\N	t
272	2	1	CR265	\N	SOUR BELTS RAINBOW	\N	t
273	3	1	CR266	\N	BARRA GRANOLA SOFT & CHEWY	\N	t
274	1	1	CR267	\N	7 Up 600ml	\N	t
275	1	1	CR268	\N	Mirinda piña 355ml	\N	t
276	1	1	CR269	\N	Welch`s naranja piña 295ml	\N	t
277	3	1	CR270	\N	Brownie bite	\N	t
278	11	1	CR271	\N	Yummi pops nacho jalapeño	\N	t
279	11	1	CR272	\N	Ranchitas nacho cinema	\N	t
280	1	1	CR273	\N	DP Me latte café vainilla	\N	t
281	2	1	CR274	\N	Toblerone Bl	\N	t
282	2	1	CR275	\N	Toblerone Dark	\N	t
283	2	1	CR276	\N	Bubbaloo XT Fresa	\N	t
284	3	1	CR277	\N	Nature Valley Almond	\N	t
285	11	1	CR278	\N	Cereal Kelloggs Zucarita Choco Mash	\N	t
286	11	1	CR279	\N	Cereal Kelloggs Choco Krispis	\N	t
287	11	1	CR280	\N	Cereal Kelloggs Froot Loops	\N	t
288	11	1	CR281	\N	Cereal Kelloggs Zucaritas	\N	t
289	1	1	CR282	\N	Fanta Berry	\N	t
290	1	1	CR283	\N	Fanta Melocoton	\N	t
291	1	1	CR284	\N	Fanta Piña	\N	t
292	1	1	CR285	\N	7up Cereza	\N	t
293	1	1	CR286	\N	Dr Pepper Cereza	\N	t
294	11	1	CR287	\N	MeLatte Café Mocha	\N	t
295	1	1	CR288	\N	MeLatte Café CappuccinO	\N	t
296	11	1	CR289	\N	Cornbits Cosecha Dorada	\N	t
297	1	1	CR290	\N	Agua Members Selection	\N	t
298	1	1	CR291	\N	Monster Ultra 475ml	\N	t
299	1	1	CR292	\N	Maxxx Energy Guarana 475ml	\N	t
300	1	1	CR293	\N	Powerade Uva 600 ml	\N	t
301	1	1	CR294	\N	Powerade Ponche Frutas 600ml	\N	t
302	1	1	CR295	\N	Monster Dragon Ice Tea475ml	\N	t
303	1	1	CR296	\N	Monster Khaos 475ml	\N	t
304	1	1	CR297	\N	Welchito 200ml	\N	t
305	1	1	CR298	\N	Welch's Fruit Punch 295ml	\N	t
306	1	1	CR299	\N	Welch's uva 295ml	\N	t
307	1	1	CR300	\N	Powerade Zero Mixed Berry 600ml	\N	t
308	2	1	CR301	\N	Halls Mentol y Eucalipto	\N	t
309	2	1	CR302	\N	Halls Yerbabuena	\N	t
310	2	1	CR303	\N	Halls Fruit Mix	\N	t
311	1	1	CR304	\N	Aloe Dos pinos 500ml	\N	t
312	1	1	CR305	\N	Jet original 350ml	\N	t
313	1	1	CR306	\N	Del valle Mango 354ml	\N	t
314	1	1	CR307	\N	Del valle Piña mandarina 330ml	\N	t
315	1	1	CR308	\N	Del valle Guayaba 330ml	\N	t
316	1	1	CR309	\N	Del valle Durazno 330ml	\N	t
317	5	1	CR310	\N	Hostess Twinkies	\N	t
318	5	1	CR311	\N	Hostess Cupcakes	\N	t
319	2	1	CR312	\N	Nucita Trisabor 15gr	\N	t
320	2	1	CR313	\N	Mashmallows Crismelos 75gr	\N	t
321	1	1	CR314	\N	Tropical de mango 350ml	\N	t
322	9	1	CR315	\N	Casado de carne mechada preparatos	\N	t
323	9	1	CR316	\N	Casado de arroz con pollo preparatos	\N	t
324	9	1	CR317	\N	Lasaña de carne preparatos	\N	t
325	9	1	CR318	\N	Gallopinto con huevo y platanos maduros Preparatos	\N	t
326	9	1	CR319	\N	Lasaña de pollo preparatos	\N	t
327	9	1	CR320	\N	Arroz con pollo y frijoles molidos preparatos	\N	t
328	9	1	CR321	\N	Cantones cuarto de kilo preparatos	\N	t
329	11	1	CR322	\N	POPPIN PALOMITAS MANTEQUILLA	\N	t
330	11	1	CR323	\N	POPPIN PALOMITAS CHEDDAR	\N	t
331	11	1	CR324	\N	POPPIN PALOMITAS DULCE SALADO	\N	t
332	11	1	CR325	\N	POPPIN PALOMITAS CARAMELO	\N	t
333	9	1	CR326	\N	Wrap de pollo y salsa ceasar preparatos	\N	t
334	9	1	CR327	\N	Cangrejo de jamon y queso arreglado preparatos	\N	t
335	9	1	CR328	\N	Arroz con leche preparatos	\N	t
336	9	1	CR329	\N	Ensalada de caracolitos con atún preparatos	\N	t
337	10	1	CR330	\N	Pistacho Wndrfl Pist	\N	t
338	2	1	CR331	\N	TRIDENT SLAB MENTA  8.5GR	\N	t
339	11	1	CR332	\N	Ranchitas Nacho Queso 23g 144s	\N	t
340	3	1	CR333	\N	MINI CHOKIS 39G	\N	t
341	2	1	CR334	\N	MINI GELATINAS 35G	\N	t
342	9	1	CR335	\N	Sandwich mostaza miel preparatos	\N	t
429	2	1	CR421	\N	BIANCHI BLANCO CARAMELO MANI 25G	\N	t
343	9	1	CR336	\N	Sandwich jamon y queso preparatos	\N	t
344	9	1	CR337	\N	Sandwich cubano preparatos	\N	t
345	1	1	CR338	\N	DR Pepper cream soda	\N	t
346	2	1	CR339	\N	Kitkat chocolate	\N	t
347	3	1	CR340	\N	Chokis Clasica 57g	\N	t
348	3	1	CR341	\N	Galletas Mantequilla Pozuelo 26g	\N	t
349	3	1	CR342	\N	Galletas  RUSH CHIPS 43g	\N	t
350	2	1	CR343	\N	Mashmellow CHOCALE 35g	\N	t
351	2	1	CR344	\N	Twix 2 cookie Bar 50.7g	\N	t
352	2	1	CR345	\N	3 MUSKETEERS	\N	t
353	1	1	CR346	\N	Tropical Té Frio limon 500ml	\N	t
354	1	1	CR347	\N	Tropical Té de Manzana Verde 500ml	\N	t
355	1	1	CR348	\N	Tropical de Frutas 350ml	\N	t
356	1	1	CR349	\N	A&W Root Beer lata	\N	t
357	1	1	CR350	\N	Milory mandarina 355ml	\N	t
358	1	1	CR351	\N	MUG ROOT BEER 355ML	\N	t
359	1	1	CR352	\N	7UP LIMON 355ML	\N	t
360	1	1	CR353	\N	MILORY KOLITA 355ML	\N	t
361	2	1	CR354	\N	Milan org	\N	t
362	2	1	CR355	\N	Milan Menta	\N	t
363	2	1	CR356	\N	Tapita	\N	t
364	2	1	CR357	\N	Guayabita	\N	t
365	11	1	CR358	\N	Mini Skittles	\N	t
366	11	1	CR359	\N	Lifesavers Big Ring Gummies	\N	t
367	2	1	CR360	\N	Mini Chocolates Tx, Snickers, 3M	\N	t
368	2	1	CR361	\N	Bubbaloo XT Mora Azul	\N	t
369	9	1	CR362	\N	MUFFIN DESAYUNO (100 g)	\N	t
370	9	1	CR363	\N	4X4 DESAYUNO (180 g)	\N	t
371	2	1	CR364	\N	HERSHEY'S CnCrèmeSnk	\N	t
372	6	1	CR365	\N	SAL ANDREWS TRADICIONAL FAMILIAR CAJI 12-S 1*	\N	t
373	3	1	CR366	\N	Tosh Barra Fresa	\N	t
374	3	1	CR367	\N	Tosh Barra Arandanos	\N	t
375	2	1	CR368	\N	CHOYS ARROZ 50g	\N	t
376	2	1	CR369	\N	478787 TuttoChoco minis	\N	t
377	2	1	CR370	\N	472890 Chocolates (Snicker paquete fiesta)	\N	t
378	1	1	CR371	\N	Hi-c Frutas 250ml	\N	t
379	1	1	CR372	\N	Hi-c uva 250ml	\N	t
380	3	1	CR373	\N	Crema surtida banano	\N	t
381	11	1	CR374	\N	Crema surtida manzana	\N	t
382	3	1	CR375	\N	Cremas surtidas fresa	\N	t
383	5	1	CR376	\N	GALLETA DE NATILLA	\N	t
384	2	1	CR377	\N	GOMAS DINO	\N	t
385	5	1	CR378	\N	BROWNIE BAUNFLY  75G	\N	t
386	2	1	CR379	\N	HALLS LIMON Y MIEL	\N	t
387	2	1	\N	\N	HALLS MENTHOL Y EUCALIPTO	Código Nova duplicado en el origen: CR400; pendiente de revisión	t
388	5	1	CR380	\N	BROWNIE BAUNFLY DULCE DE LECHE 85G	\N	t
389	1	1	CR381	\N	NESQUIK CHOCOLATE BEBIDA	\N	t
390	1	1	CR382	\N	EXPRESSO INTENSO DOLCE GUSTO	\N	t
391	1	1	CR383	\N	LATTE MACCHIATO DOLCE GUSTO	\N	t
392	1	1	CR384	\N	KIT KAT DOLCE GUSTO	\N	t
393	1	1	CR385	\N	STARBUCKS CAFE LATTE	\N	t
394	1	1	CR386	\N	CAFE LA RHEA	\N	t
395	1	1	CR387	\N	CAFE AU LAIT DOLCE GUSTO	\N	t
396	2	1	CR388	\N	Snickers	\N	t
397	2	1	CR389	\N	OKA LOKA FUSION	\N	t
398	1	1	CR390	\N	Rooster Te Limon + Cafeina	\N	t
399	1	1	CR391	\N	Electrolit UVA 625 ml	\N	t
400	1	1	CR392	\N	SPARKLING MANGO 503 ML	\N	t
401	1	1	CR393	\N	Cristal 600 ml	\N	t
402	11	1	CR394	\N	Yummis Pops Queso	\N	t
403	11	1	CR395	\N	Ranchitas Nacho Excitante	\N	t
404	11	1	CR396	\N	Ranchitas Nacho Extremo	\N	t
405	11	1	CR397	\N	Zambos Platanos Picositas 70g	\N	t
406	11	1	CR398	\N	CHEETOS FLAMING HOT 28G	\N	t
407	11	1	CR399	\N	MENEITOS CLASICOS 22G	\N	t
408	11	1	\N	\N	TAQUERITOS CHILE TOREADO 36G	Código Nova duplicado en el origen: CR400; pendiente de revisión	t
409	2	1	CR401	\N	Masmelos Choco Banana	\N	t
410	2	1	CR402	\N	Masmelos Relleno Caramelo	\N	t
411	2	1	CR403	\N	Masmelos Choco Vainilla	\N	t
412	2	1	CR404	\N	Gomitas Trululu Gusanos 80g	\N	t
413	2	1	CR405	\N	Gomitas Trululu oRo	\N	t
414	2	1	CR406	\N	Gomitas Trululu Monstry 75g	\N	t
415	2	1	CR407	\N	Gomitas Trululu Sabores 90g	\N	t
416	2	1	CR408	\N	Masmelos Choco Frutos 50g	\N	t
417	2	1	CR409	\N	Gomitas Trululu Neon 80g	\N	t
418	2	1	CR410	\N	Gomitas Trululu Aros 80g	\N	t
419	2	1	CR411	\N	Gomitas Trululu Casquito 80g	\N	t
420	2	1	CR412	\N	Gomitas Trululu Clasicas 80g	\N	t
421	3	1	CR413	\N	Bonobom Cookies Blanco 95g	\N	t
422	3	1	CR414	\N	Bonobon Cookies Original 95g	\N	t
423	2	1	CR415	\N	Brownie con pasas 75g	\N	t
424	2	1	CR416	\N	Brownie con semillas mixtas 75g	\N	t
425	5	1	CR417	\N	Alfajores 75g	\N	t
426	5	1	CR418	\N	Alfajores 65g	\N	t
427	1	1	CR419	\N	Te mango Naranja Canela	\N	t
428	2	1	CR420	\N	BIANCHI CARAMELO MANI 25G	\N	t
430	2	1	CR422	\N	BIANCHI CARAMELO MANI 40G	\N	t
431	2	1	CR423	\N	BIANCHI CHOCOMANI 30G	\N	t
432	2	1	CR424	\N	TRULULU CHOCOLORES 30G	\N	t
433	2	1	CR425	\N	TRULULU CHOCOLORES BLANCO 30G (Halloween)	\N	t
434	11	1	CR427	\N	TAQUERITOS QUESO FUSION 36G	\N	t
435	2	1	CR428	\N	GOMITA  TRULULU PIRAMIDAL 10G	\N	t
436	11	1	CR429	\N	MULTI CEREAL +PROTE NUTRI SNACKS 45G	\N	t
437	2	1	CR430	\N	STARBURST ORG 58,7G	\N	t
438	2	1	CR431	\N	TRICOPILIA BOCADITO DE GUAYABA	\N	t
439	2	1	CR432	\N	HERSHEYS WHOLE ALMONDS 41G	\N	t
440	2	1	CR433	\N	M&M CHOCOLATE	\N	t
441	2	1	CR434	\N	MILKA STRAWBERRY 100G	\N	t
442	2	1	CR435	\N	MILKA RISO SOFFIATO 100G	\N	t
443	2	1	CR436	\N	HERSHEYS COOKIES "N' CREME 43G	\N	t
444	2	1	CR437	\N	MILKA+ CAstaña 55G	\N	t
445	2	1	CR438	\N	MILKA CHOCO PAUSE 45G	\N	t
446	2	1	CR439	\N	HERSHEYS MILK CHOCOLATE 43G	\N	t
447	2	1	CR440	\N	M&M PEANUT AMARILLO 49G	\N	t
448	2	1	CR441	\N	WELCH'S FRUIT SNACKS	\N	t
449	4	1	CR442	\N	SOLDANZA PLATANITOS AJO.GARLIC 71 G	\N	t
450	4	1	CR443	\N	SOLDANZA MALANGA ORIGINALES 45G	\N	t
451	2	1	CR444	\N	MILKA ALUNE 100G	\N	t
452	2	1	CR445	\N	MILKA WHITE CHOCOLATE 100G	\N	t
453	2	1	CR446	\N	MILKA RAISINS & NUTS 100G	\N	t
454	2	1	CR447	\N	MILKA ALPINE MILK 100G	\N	t
455	2	1	CR448	\N	TWIX CHOCOLATE 50G	\N	t
456	2	1	CR449	\N	TURRON DE COCO +  CHOCOLATE 100G	\N	t
457	2	1	CR450	\N	SKITTLES ORIGINALES 61.5G	\N	t
458	11	1	CR451	\N	PRINGLES QUESO CHEDDAR 21G	\N	t
459	11	1	CR452	\N	NUASET TURRON DE MANI 50G	\N	t
460	11	1	CR453	\N	NUASET TURRON MIXTO SIN AZUCAR 60G	\N	t
461	11	1	CR454	\N	MALVAVISCOS MINI ANGELITO 40G	\N	t
462	11	1	CR455	\N	PRINGLES ORIGINAL + POTATO CRISPS 19G	\N	t
463	11	1	CR456	\N	DORITOS NACHO ATREVIDO 32G	\N	t
464	11	1	CR457	\N	SOLDANZA TOSTONES PATACONES 60G	\N	t
465	6	1	CR458	\N	SACHET MIEL ABEJA 12G	\N	t
466	2	1	CR459	\N	DELAFAILLE DORADA 50G	\N	t
467	2	1	CR460	\N	DELAFAILLE GRIS 50G	\N	t
468	2	1	CR462	\N	DELAFAILLE ROJO 50G	\N	t
469	6	1	CR463	\N	TRANQUI+TE	\N	t
470	11	1	CR461	\N	CHIRULITOS 100G	\N	t
471	11	1	CR464	\N	BRAVOS CON QUESO 150G	\N	t
472	11	1	CR465	\N	PAPIOLAS QUESO DELUXE 75G	\N	t
473	2	1	CR466	\N	OKA LOKA FUSION AZUL 14G	\N	t
474	3	1	CR467	\N	NATURE VALLEY OAT'S N HONEY 42G	\N	t
475	11	1	CR468	\N	TRONADITAS CON LIMON Y SAL 100G	\N	t
476	11	1	CR469	\N	ZIBAS MIEL MOSTAZA 23G	\N	t
477	11	1	CR470	\N	YUQUITAS CREMA Y CEBOLLA 45G	\N	t
478	11	1	CR471	\N	ZIBAS CREMA Y ESPECIAS  26G	\N	t
479	11	1	CR472	\N	ZAMBO PLATANO CON CHICHARRON 36G	\N	t
480	2	1	CR473	\N	MILKA CREME & BISCUIT/galleta 100G	\N	t
481	11	1	CR474	\N	TRULULU GOMITA SPLASH 80G	\N	t
482	11	1	CR475	\N	TRULULU GOMITA FRANKI 80G	\N	t
483	11	1	CR476	\N	TRULULU GOMITA SANDIA ACIDAS 80G	\N	t
484	11	1	CR477	\N	TRULULU GOMITA GUSANOS ACIDOS 80G	\N	t
485	11	1	CR478	\N	TRULULU GOMITA BANANA 80G	\N	t
486	11	1	CR479	\N	TRULULU GOMITA LENGUAS 80G	\N	t
487	11	1	CR480	\N	TRULULU GOMITAS FEROZ ACIDOS 77G	\N	t
488	3	1	CR481	\N	RICE KRISPIES TREATS M&M 20G	\N	t
489	3	1	CR482	\N	RICE KRISPIES TREATS CLASICA 22g	\N	t
490	2	1	CR483	\N	TRIDENT MENTA 18 CHICLES	\N	t
491	2	1	CR484	\N	TUTTO CHOCOLATE CON LECHE 20G	\N	t
492	2	1	CR485	\N	HALLS MORA AZUL-LYPTUS	\N	t
493	2	1	CR486	\N	HALLS STORNG-LYPTUS	\N	t
494	2	1	CR487	\N	HALLS CHERRY-LYPTUS	\N	t
495	2	1	CR488	\N	TRIDENT FRESH HERBAL	\N	t
496	2	1	CR489	\N	MILKA CHIPS AHOY 100G	\N	t
497	2	1	CR490	\N	TRIDENT FRESA/FRUTILLA 8,5G	\N	t
498	6	1	CR491	\N	ALEVE	\N	t
499	6	1	CR492	\N	ALKA-SERZER	\N	t
500	6	1	CR493	\N	CURITAS TRANSPARENTE 100UND	\N	t
501	2	1	CR494	\N	SNICKERS ALMENDRA 43,4G	\N	t
502	2	1	CR495	\N	SNICKERS WHITE/BLANCO 40G	\N	t
503	2	1	CR496	\N	SNICKERS ESPRESSO 41.5G	\N	t
504	3	1	CR497	\N	MINI BARRITAS FRESA 104G	\N	t
505	3	1	CR498	\N	MINI BARRITAS PIÑA 104G	\N	t
506	3	1	CR499	\N	SPONCH FRESA 90G	\N	t
507	5	1	CR500	\N	PANQUECITOS VAINILLA 100G	\N	t
508	3	1	CR501	\N	CUPCAKE BIMBOLETES SABOR VAINILLA 80G	\N	t
509	5	1	CR502	\N	CUPCAKE SABOR CHOCOLATE 80G	\N	t
510	5	1	CR503	\N	PINGUINOS 80G	\N	t
511	5	1	CR504	\N	DALMATA CREMOSO/RICO 45G	\N	t
512	11	1	CR505	\N	TAKIS ORIGINAL 49G	\N	t
513	5	1	CR506	\N	CUPCAKE BIMBOLETES CON PASAS 85G	\N	t
514	5	1	CR507	\N	BIMBOJALDRES BIGOTE DULCE DE LECHE 60G	\N	t
515	5	1	CR508	\N	GANSITO 50G	\N	t
516	5	1	CR509	\N	SUBMARINOS DE VAINILLA WOW 64G	\N	t
517	5	1	CR510	\N	SUBMARINOS FRESA 64G	\N	t
518	5	1	CR511	\N	PINGUINOS COOKIES & CREAM 80G	\N	t
519	5	1	CR512	\N	PINGUINOS TRIPLE CHOCOLATE 80G	\N	t
520	4	1	CR513	\N	CHIPS FUEGO 45G	\N	t
521	4	1	CR514	\N	CHIPS SAL 45G	\N	t
522	2	1	CR515	\N	BONBON	\N	t
523	10	1	CR516	\N	MANI JAPONES CON LIMON 80G	\N	t
524	10	1	CR517	\N	MANI JAPONES ORIGINAL 80G	\N	t
525	3	1	CR518	\N	FLORENTINA FRESA 83G	\N	t
526	3	1	CR519	\N	FLORENTINA  DULCE DE LECHE 83G	\N	t
527	2	1	CR520	\N	MILKA JOGHURT/BLANCO 100G	\N	t
528	6	1	CR521	\N	PANADOL EXTRA-FUERTE	\N	t
529	3	1	CR522	\N	CHOCO WOW WAFER 23G	\N	t
530	3	1	CR523	\N	CREMA SURTIDA NARANJA	\N	t
531	3	1	CR524	\N	CHOCO WOW CHISPAS 36G	\N	t
532	3	1	CR525	\N	CHOCO WOW SANDICH 48G	\N	t
533	3	1	CR526	\N	POZUELO CREMAS MIXTAS 31.5G	\N	t
534	2	1	CR527	\N	BARRILETE MUSIC (50) UND	\N	t
535	2	1	CR528	\N	CARAMELO REVOLCON (50)	\N	t
536	1	1	CR529	\N	JET KOLITA 600ML	\N	t
537	1	1	CR530	\N	JET MANGO 600ML	\N	t
538	1	1	CR531	\N	TROPICAL TE MELOCOTON 500ML	\N	t
539	1	1	CR532	\N	DEL VALLE MANZANA LATA 430ML	\N	t
540	1	1	CR533	\N	TROPICAL TE CERO MANZANA KIWI 500ML	\N	t
541	1	1	CR534	\N	TROPICAL TE BLANCO ARANDANOS 500ML	\N	t
542	1	1	CR535	\N	H2 OH! LIMA LIMON 600ML	\N	t
543	1	1	CR536	\N	PEPSI ZERO 355ML	\N	t
544	1	1	CR537	\N	JET CERO 600ML	\N	t
545	5	1	CR538	\N	Zarcereña BIZCOCHO CASERO 150G	\N	t
546	5	1	CR539	\N	BIZCOCHO CASERO 75G	\N	t
547	11	1	CR540	\N	CHIPPS TOCINITOS 19G	\N	t
548	11	1	CR541	\N	TOCINITOS LIMON 85G	\N	t
549	2	1	CR542	\N	PALETA TIPITIN CERVECITA	\N	t
550	1	1	CR543	\N	TE REACTE 20 SOBRES	\N	t
551	2	1	CR544	\N	CONFITE ROSSANA RELLENO CREMOSO	\N	t
552	2	1	CR545	\N	CONFITE ROSSANA PISTACCHIO	\N	t
553	1	1	CR546	\N	TE BLANCO CERO FRUTAS TROPICALES 500ML	\N	t
554	11	1	CR547	\N	TOREADITOS LIMON EXPLOSIVO 90G	\N	t
555	11	1	CR548	\N	CHIPPS CANTARIN BARBACOA 24G	\N	t
556	4	1	CR549	\N	PIAZZA BARQUILLO DE FRESA 45G	\N	t
557	3	1	CR550	\N	PIAZZA BARQUILLO CHOCOLATE 45G	\N	t
558	11	1	CR551	\N	CHIS WIS 69G	\N	t
559	3	1	CR552	\N	Arcor Galleta Tortini Choco 90g	\N	t
560	3	1	CR553	\N	ARCOR TORTINI FRESA 90G	\N	t
561	3	1	CR554	\N	ARCOR WAFER CHOCO 105G	\N	t
562	3	1	CR555	\N	ARCOR WAFER FRESA 105G	\N	t
563	3	1	CR556	\N	PIAZZA BARQUILLO VAINILLA 45G	\N	t
564	11	1	CR557	\N	CANTARIN LIMON	\N	t
565	11	1	CR558	\N	CANTARIN QUESO CHEDDAR	\N	t
566	3	1	CR559	\N	FORMIS CHOCOLATE	\N	t
567	3	1	CR560	\N	FORMIS FRESA	\N	t
568	2	1	CR561	\N	GOMITAS DE FRUTAS	\N	t
569	3	1	CR562	\N	ARCOR WAFER VAINILLA	\N	t
570	3	1	CR563	\N	ARCOR WAFER BONBON	\N	t
571	4	1	CR564	\N	PLATANITOS LIMON Y SAL	\N	t
572	2	1	CR565	\N	GOMITA DE YOGURT	\N	t
573	11	1	CR566	\N	ZAMBOS TAJIN 70G	\N	t
574	8	1	CR567	\N	YOGURT DELIGURT FRUTAS 200ML	\N	t
575	8	1	CR568	\N	YOGURT DELIGURT FRESA 200ML	\N	t
576	8	1	CR569	\N	YOGURT FRUTAS 200ML	\N	t
577	8	1	CR570	\N	YOGURT DELIGURT FRESA  200ML	\N	t
578	1	1	CR571	\N	DELACTOMY 250ML	\N	t
579	1	1	CR572	\N	LECHE DECREMADA BLANCO 250ML	\N	t
580	11	1	CR573	\N	BARRA DE GRANOLA MARACUA 25G	\N	t
581	11	1	CR574	\N	BARRA DE GRANOLA+PROTEINA 35G	\N	t
582	11	1	CR575	\N	BARRA DE UCHUVA Y ALMENDRA SIN AZUCAR 25G	\N	t
583	11	1	CR576	\N	LAKY MEN RES 75G	\N	t
584	11	1	CR577	\N	LAKY SABOR CAMARON 75G	\N	t
585	11	1	CR578	\N	LAKYSABOR POLLO 75G	\N	t
586	3	1	CR579	\N	FORMIS CHOCOLATE 43G	\N	t
587	3	1	CR580	\N	GALLETA INTEGRAL CON PASAS 24G	\N	t
588	3	1	CR581	\N	GALLETA MARACUYA Y CHIA 24G	\N	t
589	3	1	CR582	\N	GALLETA MACADAMIA 24G	\N	t
590	1	1	CR583	\N	MAX ENERGY 350ML	\N	t
591	3	1	CR584	\N	CHOKIS RELLENA 90G	\N	t
592	2	1	CR585	\N	M&M CHOCOLATE 45G	\N	t
593	4	1	CR586	\N	SOLDANZA PLATAITO CREMA Y SEBOLLA 42G	\N	t
594	4	1	CR587	\N	SOLDANZA BANANA CHIPS 36G	\N	t
595	11	1	CR588	\N	TAKIS XPLOSION 56G	\N	t
596	11	1	CR589	\N	CHICHARRONES PICOSITOS 22G	\N	t
597	3	1	CR590	\N	MINI BARRITAS MORAS 104G	\N	t
598	10	1	CR591	\N	MANI BARBACOA 70G	\N	t
599	5	1	CR592	\N	OLLITAS 65G	\N	t
600	11	1	CR593	\N	YUMMIX TROPICAL 31G	\N	t
601	1	1	CR594	\N	POWER AZUL 600ML	\N	t
602	1	1	CR595	\N	ALOE VERA KING 340ML	\N	t
603	6	1	CR596	\N	COLGATE PLAX 60ML	\N	t
604	1	1	CR597	\N	MUG ROOT BEER 600ML	\N	t
605	1	1	CR598	\N	DEL MONTE MANZANA 330ML	\N	t
606	1	1	CR599	\N	DEL MONTE MELOCOTON 330ML	\N	t
607	1	1	CR600	\N	DEL MONTE PEAR 330ML	\N	t
608	1	1	CR601	\N	H2OH LIMONATA 600ML	\N	t
609	2	1	CR602	\N	TRIDENT COOL BUBBLE 18U 30,6G	\N	t
610	2	1	CR603	\N	TRIDENT FRESHMINT 18U 30,6G	\N	t
611	7	1	CR604	\N	No disponible	\N	t
612	7	1	CR605	\N	PALETA 2 PINOS ALASKA NATILLA PALETA 45G	\N	t
613	7	1	CR606	\N	HELADO 2 PINOS CERO GRADOS	\N	t
614	11	1	CR607	\N	PURE JOY QUESO CHEDDAR PIÑA 28G	\N	t
615	11	1	CR608	\N	ZAMBOS PLATANO ORIGINALES 36G	\N	t
616	3	1	CR609	\N	GALLETA LIMON SURTIDA	\N	t
617	8	1	CR610	\N	GRIEGO PLUS FRUTOS ROJOS 200G	\N	t
618	3	1	CR611	\N	MERENDINA LIMON	\N	t
619	2	1	CR612	\N	PASTILLAS CHAO CEREZA 20.4G	\N	t
620	3	1	CR613	\N	GALLETA SURTIDA UVA	\N	t
621	8	1	CR614	\N	YOGURT GRIEGO PIÑA - MORA DOS PINOS	\N	t
622	1	1	CR615	\N	MONSTER PIPELINE PUNCH 473ML	\N	t
623	2	1	CR616	\N	CHOYS MANI 50G	\N	t
624	1	1	CR617	\N	RAPTOR LATA ORG 473ML	\N	t
625	1	1	CR618	\N	BIG COLA 625ML	\N	t
626	1	1	CR619	\N	ELECTROLIT FRESA 625ML	\N	t
627	11	1	CR620	\N	VAPOS TRIPLE MANGO	\N	t
628	1	1	CR621	\N	(DP) LECHE PROTEINA FRESA 250ml	\N	t
629	11	1	CR622	\N	VAPO CHERRY PARADISE	\N	t
630	3	1	CR623	\N	GALLETAS AVENA Y CHOCOLATE COSECHA DORADA 23G	\N	t
631	11	1	CR624	\N	VAPO DE KIWI	\N	t
632	1	1	CR625	\N	ROOSTER TE VERDE	\N	t
633	11	1	CR626	\N	VAPO DE BLUE RAZZ LEMON	\N	t
634	11	1	CR627	\N	VAPO DE GUMMY BEAR	\N	t
635	6	1	CR628	\N	PANADOL SINUSITIS 16TABLETAS	\N	t
636	11	1	CR629	\N	PRO YUQUITA SALADAS 42,5G	\N	t
637	1	1	CR630	\N	ROOSTER TE MELOCOTÓN	\N	t
638	6	1	CR631	\N	TE DE MANZANILLA 25 SOBRES	\N	t
639	11	1	CR632	\N	ALMENDRAS SOLDANZA 45G	\N	t
640	3	1	CR633	\N	GALLETA OREO ORG 36G	\N	t
641	1	1	CR634	\N	TROPICAL TE BLANCO 250ML	\N	t
642	1	1	CR635	\N	TROPICAL TE NEGRO MELOCOTÓN 250ML	\N	t
643	1	1	CR636	\N	COCA COLA OREO 354ML	\N	t
644	11	1	CR637	\N	CABLE TIP C GD-11T BLANCO	\N	t
645	11	1	CR638	\N	CABLE MICRO B K-41 BLANCO	\N	t
646	11	1	CR639	\N	TAKIS FUEGO 49G	\N	t
647	11	1	CR640	\N	SPONCH S^MORE 90G	\N	t
648	4	1	CR641	\N	CHIPS JALAPEÑO 45G	\N	t
649	4	1	CR642	\N	CHIPS HABANERO 36G	\N	t
650	11	1	CR643	\N	TRULULU DE CHOCO BANANA 50G	\N	t
651	11	1	CR644	\N	PRINGLES SOUR CREAM & ONION 21G	\N	t
652	2	1	CR645	\N	MILKA HAPPY COWS 100G	\N	t
653	2	1	CR646	\N	SNICKERS 50G	\N	t
654	1	1	CR647	\N	FURY ENERGY 500ML	\N	t
655	2	1	CR648	\N	Zarcereña BIZCOTELAS 220G	\N	t
656	5	1	CR649	\N	QUEQUITO 270G	\N	t
657	5	1	CR650	\N	ZARCEREÑA PALITOS DE QUESO 140G	\N	t
658	11	1	CR651	\N	GRANOLA ARANDANOS &  ALMENDRAS SIN AZUCAR 40G	\N	t
659	3	1	CR652	\N	QUAKER GALLETAS AVENA GRANOLA 38G	\N	t
660	3	1	CR653	\N	GALLETAS AVENA GRANOLA COSECHA DORADA 45G	\N	t
661	5	1	CR654	\N	Zarcereña GALLETA CON GUAYABA  360G	\N	t
662	5	1	CR655	\N	ELUSTRADOS ZARCEREÑA 150G	\N	t
663	1	1	CR656	\N	JET CERO AZUCAR 350ML	\N	t
664	6	1	CR657	\N	ALKASELTZER NEGRO	\N	t
665	6	1	CR658	\N	ALKASELTZER AZUL	\N	t
666	1	1	CR659	\N	MONSTER PIPELINE PUNCH 473ML	\N	t
667	1	1	CR660	\N	MONSTER WHITE  PINEAPPLE 473ML	\N	t
668	1	1	CR661	\N	ELECTROLIT ZERO MORA AZUL 625ML	\N	t
669	1	1	CR662	\N	ELECTROLIFE ZERO DE PONCHE DE FRUTAS 625ML	\N	t
670	1	1	CR663	\N	ELECTROLIFE ZERO LIMON 625ML	\N	t
671	1	1	CR664	\N	ELECTROKIFE ZERO BLUE RASPBERRY 625ML	\N	t
672	1	1	CR665	\N	ROOSTER ZERO COOL BERRIES  600ML	\N	t
673	2	1	CR666	\N	TRITAPITA 25G	\N	t
674	3	1	CR667	\N	GALLETA ARROZ CHOCOLATE 25G NUTRI SNACKS	\N	t
675	1	1	CR668	\N	ROOSTER ZERO SANDIA 600ML	\N	t
676	3	1	CR669	\N	GALLETA ARROZ FRUTOS ROJOS NUTRI SNACKS 25G	\N	t
677	1	1	CR670	\N	ROOSTER ZERO LIMONADA FRESA 600ML	\N	t
678	1	1	CR671	\N	BIG COLA 250ML	\N	t
679	1	1	CR672	\N	GATORADE ZERO NARANJA 600ML	\N	t
680	1	1	CR673	\N	ELECTROLIT MARACUYA 625ML	\N	t
681	6	1	CR674	\N	SABA 10/TOALLA	\N	t
682	7	1	CR675	\N	ALASKA CONO 70G	\N	t
683	1	1	CR676	\N	CRISTAL 600ML tapa corriente	\N	t
684	3	1	CR677	\N	GALLETA RUSH BITES CHIPS 30G	\N	t
685	2	1	CR678	\N	BARRILETE CHOCO MASMELOS 50G	\N	t
686	5	1	CR679	\N	ARTESANO BANANO CON NUEZ 78G	\N	t
687	3	1	CR680	\N	FORMIS DE FRESA 43G	\N	t
688	4	1	CR681	\N	SOLDANZA MADURITOS DULCE PICANTE 71G	\N	t
689	2	1	CR682	\N	TRULULU CHOCOLORES BLANCO 30G (org)	\N	t
690	4	1	CR683	\N	SOLDANZA MADURITOS SIN AZUCAR 71G	\N	t
691	2	1	CR684	\N	COLOMBINA GRISSLY  GOMITAS TIBURON 50G	\N	t
692	3	1	CR685	\N	CRAKEÑAS SODA 25G	\N	t
693	3	1	CR686	\N	CRAKEÑAS CLUB COLOMBINA 25,5G 250	\N	t
694	3	1	CR688	\N	GALLETAS AVENA PASAS COSECHA DORADA 45G	\N	t
695	2	1	CR687	\N	M&M MANÍ 45G	\N	t
696	3	1	CR689	\N	GALLETAS SOYA VAINILLA COSECHA DORADA 45G	\N	t
697	2	1	CR690	\N	M&M CHOCOLATE ORIGINAL 47.9G	\N	t
698	2	1	CR691	\N	SKITTLES WILDBERRY 61.5G	\N	t
699	11	1	CR692	\N	CUADRITOS DE MAIZ CARAMELO SALADO 25G	\N	t
700	2	1	CR693	\N	TRULULU GOMITAS FRESITAS 80G	\N	t
701	11	1	CR694	\N	BARRA DE ARANDANO Y ALMENDRA 25G	\N	t
702	11	1	CR695	\N	BARRA SIN AZUCAR CARAMELO 25G	\N	t
703	7	1	CR696	\N	CHOCO BIGER 90G	\N	t
704	7	1	CR697	\N	PALETA TAPITA 85G	\N	t
705	3	1	CR698	\N	GALLETA QUAKER FRUTOS ROJO 38GS	\N	t
706	3	1	CR699	\N	GALLETA QUAKER AVENA MANZANA CANELA 36G	\N	t
707	3	1	CR700	\N	MERENDINA FRESA 20G	\N	t
708	3	1	WAFER FRESA COLOMBINA 14G	\N	WAFER FRESA COLOMBINA 14G	\N	t
709	1	1	CR701	\N	VK FRUTAS ENERGY DRINK CERO 473ML	\N	t
710	4	1	CR702	\N	SOLDANZA BANANITOS SALADITOS 71G	\N	t
711	4	1	CR703	\N	SOLDANZA PLATANITOS LIMON 71G	\N	t
712	3	1	CR704	\N	PRINCIPE CON RELLENO DELIMON 85G	\N	t
713	3	1	CR705	\N	PRINCIPE SABOR A CHOCOLATE 85G	\N	t
714	11	1	CR706	\N	BROWN CHOCOLATE 55G	\N	t
715	11	1	CR707	\N	CHOCO NITO 39G	\N	t
716	2	1	CR708	\N	HERSHEY'S NUGGETS	\N	t
717	8	1	CR709	\N	DELIGURT ARANDANO 200ML	\N	t
718	8	1	CR710	\N	YOGURT GRIEGO POST ENTRENO KIWI 200ML	\N	t
719	8	1	CR711	\N	YOGURT GRIEGO POST ENTRENO FRESA-BANANO 200ML	\N	t
720	1	1	CR712	\N	TROPICAL TE FRIO LIMON 250ML	\N	t
721	1	1	CR713	\N	TROPICAL DE FRUTAS 250ML	\N	t
722	1	1	CR714	\N	AGUA MEMBER'S SELECTION 236ML	\N	t
723	2	1	CR715	\N	FREEGELLS  NEGRO 27,9G	\N	t
724	3	1	CR716	\N	GALLETA SUGAR FREE LIMA LIMON NUTRI SNACKS 24g	\N	t
725	3	1	CR717	\N	GALLETA SUGAR FREE NARANJA  NUTRI SNACKS 24g	\N	t
726	2	1	CR718	\N	KICK MANI 35G	\N	t
727	2	1	CR719	\N	CLORETS 16,8G	\N	t
728	2	1	CR720	\N	CABSHA 480G	\N	t
729	1	1	CR721	\N	Powerade AZUL  600ml	\N	t
730	8	1	CR722	\N	BIO ARANDANO 200ML	\N	t
731	2	1	CR723	\N	CHAO MENTA 1X24	\N	t
732	4	1	CR724	\N	ZIBAS CLASICAS 23G	\N	t
733	11	1	CR725	\N	PUREJOY PIÑA Y QUESO CHEDDAR 14G	\N	t
734	11	1	CR726	\N	PUREJOY TROZO DE PIÑA Y BANANO 14G	\N	t
735	11	1	CR727	\N	PUREJOY BANANO CRUJIENTE 14G	\N	t
736	11	1	CR728	\N	PUREJOY QUESO CHEDDAR CRUJIENTE 14G	\N	t
737	2	1	CR729	\N	MILKA OREO 100G	\N	t
738	11	1	CR730	\N	PROTEINA+ARANDANO 8G	\N	t
739	10	1	CR731	\N	CHOCO DISK MANÍ 20G	\N	t
740	2	1	CR732	\N	POPI BON BON BUM CLEAR	\N	t
741	2	1	CR733	\N	POPI BON BON BUM SANDÍA	\N	t
742	2	1	\N	\N	HERSHEYS ALMENDRA 41G	Código Nova duplicado en el origen: CR734; pendiente de revisión	t
743	5	1	\N	\N	BROWN DULCE DE LECHE 60G	Código Nova duplicado en el origen: CR734; pendiente de revisión	t
744	3	1	CR735	\N	HOJALDRITAS 45G	\N	t
745	11	1	CR736	\N	BIG-MIX FUEGO 40G	\N	t
746	11	1	CR737	\N	TAKIS BLUE HEAT 65G	\N	t
747	1	1	CR738	\N	TE MELOCOTON CERO 500ML	\N	t
748	3	1	CR739	\N	GALLETA BOKITAS ORIGINAL 27G	\N	t
749	11	1	CR740	\N	ZIBAS QUESO 23G	\N	t
750	2	1	CR741	\N	MILKA ALPENMILCH 100G	\N	t
751	2	1	CR742	\N	MILKA  JOGHURT 100G	\N	t
752	3	1	CR743	\N	PRINCIPE CHOCODROPS 57G	\N	t
753	3	1	CR744	\N	PRINCIPE CHOCOLATEBLANCO 85G	\N	t
754	3	1	CR745	\N	GALLETA CHOCO-CAPPUCCINO 24G	\N	t
755	11	1	CR746	\N	LAKY SABOR MARISCOS 75G	\N	t
756	2	1	CR747	\N	TRULULU DISPLAY SABORES ACIDITO 30G	\N	t
757	9	1	CR748	\N	CLUB SANDWICH 180G	\N	t
758	9	1	CR749	\N	SANDWICH- CARNE MECHADA 190G	\N	t
759	9	1	CR750	\N	SANDWICH DE ATUN 185G	\N	t
760	8	1	CR751	\N	YOGURT GRIEGO DE ALMENDRAS 200ML	\N	t
761	8	1	CR752	\N	YOGURT GRIEGO DE ARANDANO 200ML	\N	t
762	6	1	CR753	\N	VAPORIZADOR KADOBAR STRAWBERRY BANANA	\N	t
763	6	1	CR754	\N	VAPORIZADOR SMOK SPACEMAN TROPICAL LIME BLAST	\N	t
764	6	1	CR755	\N	VAPORIZADOR SMOK SPACEMAN BLACKBERRY PEACH LEMON	\N	t
765	11	1	CR756	\N	SOLDANZA HOJUELAS DE MALANGA 32G	\N	t
766	11	1	CR757	\N	VAPORIZADOR SMOK SPACEMAN FLORIDA LEMONADE	\N	t
767	11	1	CR758	\N	VAPORIZADOR SMOK SPACEMAN HONOLULU BLUE	\N	t
768	11	1	CR759	\N	VAPORIZADOR SMOK SPACEMAN PEACH BERRY ICE	\N	t
769	2	1	CR760	\N	CHOCOLATE MILKA OREO WHITE 100G	\N	t
770	11	1	CR761	\N	PALOMITAS MIXTAS MANTEQUILLA CARAMELO  60G	\N	t
771	11	1	CR762	\N	GOLDEN PAPAS TOSTADAS 30G	\N	t
772	6	1	CR763	\N	HILO DENTAL EXPANSION PLUS	\N	t
773	2	1	CR764	\N	MINI KICK 13,5G	\N	t
774	3	1	CR765	\N	WAFER NUTTY CRISPI 17.2G	\N	t
775	3	1	CR766	\N	COSECHA DORADA GALLETA AVENA Y CHOCOLATE 45G	\N	t
776	4	1	CR767	\N	RANCHITA PIZZA 70G	\N	t
777	4	1	CR768	\N	RANCHITA NACHO EXTRA 70G	\N	t
778	5	1	CR769	\N	PALITOS DULCES 140G	\N	t
779	4	1	CR770	\N	CANTARIN BARBACOA 100G	\N	t
780	11	1	CR771	\N	TAKIS HUAKAMOLES 65G	\N	t
781	2	1	CR772	\N	KRONK DE CHOCOLATE 33G	\N	t
782	2	1	CR773	\N	KRONK DE MANI 33G	\N	t
783	2	1	CR774	\N	KRONK DE FRESA 33G	\N	t
784	8	1	CR775	\N	YOGURT BIO FRESA 200ML	\N	t
785	11	1	CR776	\N	ATUN PRONTO VEGETAL	\N	t
786	11	1	CR777	\N	ATUN PRONTO TROZOS	\N	t
787	2	1	CR778	\N	CHOCO BREAK	\N	t
788	1	1	CR779	\N	SHASTA UVA 355ML	\N	t
789	1	1	CR780	\N	SHASTA LIMA LIMÓN 355ML	\N	t
790	3	1	CR781	\N	NUTRI GALLETA SIN AZUCAR NARANJA	\N	t
791	3	1	CR782	\N	NUTRI GALLETA SIN AZUCAR MANZANA Y CANELA	\N	t
792	1	1	CR783	\N	GATORADE  ZERO CELESTE 600ML	\N	t
793	3	1	CR784	\N	CRAKENAS SALADAS 25G	\N	t
794	1	1	CR785	\N	JUGO DE NARANJA 500ML	\N	t
795	8	1	CR786	\N	YOGURT BIO CIRUELA 200ML	\N	t
796	5	1	CR787	\N	ARTESANO DE ZANAHORIA  CON ALMENDRA 78G	\N	t
797	3	1	CR788	\N	NUTRI GALLETA SIN AZUCAR LIMA LIMON	\N	t
798	3	1	CR789	\N	NUTRI GALLETA SIN AZUCAR MARACUYA	\N	t
799	3	1	CR790	\N	NUTRI GALLETA SIN AZUCAR CHOCOLATE	\N	t
800	10	1	CR791	\N	PISTACHIOS CHILLI ROASTED 21G	\N	t
801	10	1	CR792	\N	PISTACHIOS HONEY ROASTED 21G	\N	t
802	10	1	CR793	\N	PISTACHIOS ROASTED & SALTED 21G	\N	t
803	2	1	CR794	\N	TODAY WAFER FRESA 200G	\N	t
804	2	1	CR795	\N	TODAY WAFER VAINILLA 200G	\N	t
805	11	1	CR796	\N	ZAMBO TAJIN 36G	\N	t
806	3	1	CR797	\N	TREATS RICE KRISPIES 11G	\N	t
807	1	1	CR798	\N	SPARKLING PINA	\N	t
808	1	1	CR799	\N	SPARKLING FRUTOS ROJOS	\N	t
809	11	1	CR800	\N	LAKY MEN SABOR CAMARON	\N	t
810	11	1	CR801	\N	LAKY MEN SABOR GALLINA	\N	t
811	1	1	CR802	\N	ELECTROLYTE TROPICAL	\N	t
812	1	1	CR803	\N	ELECTROLYTE NARANJA	\N	t
813	1	1	CR804	\N	ELECTROLYTE UVA	\N	t
814	1	1	CR805	\N	ELECTROLYTE PONCHE DE FRUTAS	\N	t
815	2	1	CR806	\N	OKA LOKA NANOS	\N	t
816	1	1	CR807	\N	Del  Valle Mango y Fresa 330ml	\N	t
817	1	1	CR808	\N	AGUA PURIFICADA ALMA 600ML	\N	t
818	1	1	CR809	\N	TE BLANCO CERO 500ML	\N	t
819	11	1	CR810	\N	ROSQUITAS HORNEADAS DE YUCA CON CHIA 20G	\N	t
820	6	1	CR811	\N	SABA BUENAS NOCHES	\N	t
821	6	1	CR812	\N	CONDON PRUDENCE SABOR Y AROMA FRESA 3UNID	\N	t
822	6	1	CR813	\N	CONDON PRUDENCE SENSITIVE 3UNID	\N	t
823	6	1	CR814	\N	CONDON PRUDENCE  CARIBBEAN MIX 5UNID	\N	t
824	6	1	CR815	\N	CONDON PRUDENCE CLASICO 3UNID	\N	t
825	6	1	CR816	\N	CONDON PRUDENCE RETARDANTE 3UNID	\N	t
826	6	1	CR817	\N	CONDON PRUDENCE NEON 3UNID	\N	t
827	6	1	CR818	\N	CONDON PRUDENCE SABOR Y AROMA MIX 5UNID	\N	t
828	6	1	CR819	\N	CONDON DUREX CLASICO 3UNID	\N	t
829	6	1	CR820	\N	CONDON PRUDENCE NEON 3UNID	\N	t
830	11	1	CR821	\N	AVENA TOSH CHOCOLATE 40G	\N	t
831	11	1	CR822	\N	AVENA TOSH ARANDANOS 40G	\N	t
832	11	1	CR823	\N	AVENA TOSH UINOA VAINILLA 40G	\N	t
833	11	1	CR824	\N	GALLETA SODA CLASICA	\N	t
834	3	1	CR825	\N	GALLETA SODA QUESO	\N	t
835	3	1	CR826	\N	GALLETA SODA ESPECIES	\N	t
836	3	1	CR827	\N	GALLETA CREMAS DARK	\N	t
837	3	1	CR828	\N	GALLETA CLUB EXTRA	\N	t
838	3	1	CR829	\N	CHOCO CHISPAS 37G	\N	t
839	4	1	CR830	\N	RANCHITA NACHO QUESO 70G	\N	t
840	1	1	CR831	\N	OKF WAKE UP	\N	t
841	11	1	CR832	\N	MEZCLA PREMIUN 60G	\N	t
842	4	1	CR833	\N	RANCHITA NACHO ATREVIDO 70G	\N	t
843	11	1	CR834	\N	FUSION DE FRUTA Y NUECES 68G	\N	t
844	3	1	CR835	\N	RECREO CHOCOLATE	\N	t
845	3	1	CR836	\N	CREMAS CHOCOFRESA	\N	t
846	3	1	CR837	\N	ZARCERENA COSTILLAS DE GUAYABA 240G	\N	t
847	11	1	CR838	\N	YUMMI POPS NACHO JALAPENO CREAM 130G	\N	t
848	11	1	CR839	\N	YUMMI POPS NACHO QUESO BLANCO 130G	\N	t
849	2	1	CR840	\N	TUTTO SIN AZUCAR 180G	\N	t
850	3	1	CR841	\N	POZUELO CROCANTS 32G	\N	t
851	3	1	CR842	\N	MERENDINA ARROLLADITO	\N	t
852	3	1	CR843	\N	CHICKY CHOCO BUM	\N	t
853	3	1	CR844	\N	CHICKY FLIP CHOCO	\N	t
854	3	1	CR845	\N	CHICKY FLIP CHOCO WHITE	\N	t
855	3	1	CR846	\N	BOKITAS QUESO AMARILLO	\N	t
856	3	1	CR847	\N	BOKITAS RELLENA DE QUESO BLANCO	\N	t
857	3	1	CR848	\N	BOKITAS ORIG	\N	t
858	11	1	CR849	\N	SOLDANZA YUQUITA ORIGINALES 45G	\N	t
859	11	1	CR850	\N	TRULULU MASMELO FRANKI RELLENO 50G	\N	t
860	11	1	CR851	\N	TRULULU GOMITAS ARCOIRIS 80G	\N	t
861	11	1	CR852	\N	TRULULU GOMITAS DINOS 80G	\N	t
862	11	1	CR853	\N	TRULULU MASMELOS RELLENOS FRESA 50G	\N	t
863	2	1	CR854	\N	TRIDENT SANDIA 8,5G	\N	t
864	2	1	CR855	\N	MILKA OREO BROWNIE 100G	\N	t
865	2	1	CR856	\N	MILKA CARAMEL-CREME 100G	\N	t
866	4	1	CR857	\N	SOLDANZA PLATANITOS SALADITOS 71G	\N	t
867	3	1	CR858	\N	PRINCIPE LEMON DROPS 57G	\N	t
868	4	1	CR859	\N	CHIPS JALAPEÑO 36G	\N	t
869	11	1	CR860	\N	ZAMBO PLATANO TAJIN 140G	\N	t
870	11	1	CR861	\N	ZAMBO SALSA VRDE 140G	\N	t
871	11	1	CR862	\N	ZAMBOS PICOSITAS 140G	\N	t
872	11	1	CR863	\N	TOSTADITAS DE MAIZ 18G	\N	t
873	11	1	CR864	\N	PALITOS DE MAIZ Y QUESO CHEDDAR 30G	\N	t
874	3	1	CR865	\N	YIPPY NARANJA	\N	t
875	11	1	CR866	\N	BORRACHOS DE GUAYABA 280G	\N	t
876	3	1	CR867	\N	GALLETA SALMA 18G	\N	t
877	11	1	CR868	\N	TAKIS INTENCE MACHO 28G	\N	t
878	7	1	CR869	\N	PALETA PROTEINA DULCE DE LECHE	\N	t
879	1	1	CR870	\N	FRESCO LECHE CARAMELO	\N	t
880	7	1	CR871	\N	HELADO MMIO PECANAS	\N	t
881	6	1	CR872	\N	DORUSA/AFEITAR	\N	t
882	1	1	CR873	\N	CANADA DRY SODA 600ML	\N	t
883	10	1	CR874	\N	YUMMI NUTS OMEGA MIX 15G	\N	t
884	11	1	CR875	\N	GOLDEN PATACON 60G	\N	t
885	1	1	CR876	\N	TROPICAL ARANDANO 250ML	\N	t
886	1	1	CR877	\N	TROPICAL CERO TE BLANCO ARANDANO 500ML	\N	t
887	2	1	CR878	\N	TUTTO CARAMELO	\N	t
888	2	1	CR879	\N	CHOYS BROWNIE	\N	t
889	3	1	CR880	\N	YEMAS 52G	\N	t
890	2	1	CR881	\N	TUTTO DE 20G	\N	t
891	3	1	CR882	\N	GALLETA NUTRI SNACK MACADAMIA 48G	\N	t
892	5	1	CR883	\N	BARQUILLOS JACKS COMBINADOS 42G	\N	t
893	11	1	CR884	\N	PALOMITAS POPI CARAMELO 85G	\N	t
894	2	1	CR885	\N	TRULULU HELADO FRESA 54G	\N	t
895	3	1	CR886	\N	GALLETA ENERGY 48G	\N	t
896	2	1	CR887	\N	TRULULUS NANOS 100G	\N	t
897	11	1	CR888	\N	CUADRITOS DE MAÍZ QUESO CEBOLLÍN 25G	\N	t
898	11	1	CR889	\N	POPI PALOMITAS CARAMELO MANÍ 27G	\N	t
899	11	1	CR900	\N	NACHOS JACKS QUESO Y PICANTE 25G	\N	t
900	11	1	CR901	\N	NACHOS JACKS QUESO 25G	\N	t
901	2	1	CR902	\N	ALFAJOR BON O BON 40G	\N	t
902	11	1	CR903	\N	PALITROKES ACEITE DE OLIVA 35G	\N	t
903	11	1	CR904	\N	PALITROKES QUESO 35G	\N	t
904	2	1	CR905	\N	BON O BON 15G	\N	t
905	2	1	CR906	\N	HERSHEYS MILK CHOCOLATE 43G	\N	t
906	2	1	CR907	\N	BARRA BON O BON 48G	\N	t
907	2	1	CR908	\N	GOMITAS GRISSLY 50G	\N	t
908	5	1	CR910	\N	BROWNIE ARANDANOS 2.80Z	\N	t
909	3	1	CR911	\N	WAFER FRESA 14GFRESA 14G	\N	t
910	3	1	CR912	\N	CREMASVAINILLA 64G	\N	t
911	11	1	CR913	\N	BARRA CEREAL ARANDANOS 27G	\N	t
912	3	1	CR914	\N	COCANAS 84G	\N	t
913	3	1	CR915	\N	BOKITAS QUESO BLANCO 70G	\N	t
914	3	1	CR916	\N	CREMAS CHOCOLATE 64G	\N	t
915	3	1	CR917	\N	RECREO VAINILLA 99G	\N	t
916	3	1	CR918	\N	WAFER CHOCOLATE  14G COLOMBINA	\N	t
917	3	1	CR919	\N	OREO COCA COLA 36G	\N	t
918	2	1	CR920	\N	MENTA 20G	\N	t
919	2	1	CR921	\N	MENTA VIOLETA 20G	\N	t
920	2	1	CR922	\N	MENTACHAO FRESA14G	\N	t
921	11	1	CR923	\N	ATUN AZUL SPLASH 140G	\N	t
922	2	1	CR925	\N	CONFITE ALKA3.7G	\N	t
923	3	1	CR926	\N	ARROLLADITO MERIDIANA 55G	\N	t
924	11	1	CR927	\N	BARRA GRANOLA NUTRI 25G	\N	t
925	11	1	CR928	\N	SARDINA PRONTO 75G	\N	t
926	11	1	CR929	\N	SOPA TALLARINES 64G	\N	t
927	2	1	CR930	\N	GELAKIN  2D 200G	\N	t
928	11	1	CR931	\N	TORTILLA JALAPEÑO 75G	\N	t
929	11	1	CR932	\N	CHURRITOS 40G	\N	t
930	11	1	CR933	\N	QUESITOS 45G	\N	t
931	11	1	CR934	\N	MAIZ JALAPEÑO 52G	\N	t
932	11	1	CR935	\N	CENTAVITOS 40G	\N	t
933	11	1	CR936	\N	ALBOROTO 90G	\N	t
934	1	1	CR937	\N	AGUA  ALMA  1L	\N	t
935	1	1	CR938	\N	TROPICAL CERO FRANBUESA 500ML	\N	t
936	1	1	CR939	\N	TROPICAL CERO MANDARINA 500ML	\N	t
937	1	1	CR940	\N	TROPICAL NEGRO LIMON 350ML	\N	t
938	1	1	CR941	\N	TROPICAL TE NEGRO MELOCOTON 350ML	\N	t
939	1	1	\N	\N	7UP 355ML	Código Nova duplicado en el origen: CR942; pendiente de revisión	t
940	1	1	\N	\N	COCA COLA DESCAFEINADA 355ML	Código Nova duplicado en el origen: CR942; pendiente de revisión	t
941	6	1	CR943	\N	REXONA FEMENINO 30ML	\N	t
942	6	1	CR944	\N	REXONA MASCULINO 30ML	\N	t
943	2	1	CR945	\N	RICE TREATS CHOCOLATE 11G	\N	t
944	1	1	CR 943	\N	RAPTOR MANGO Y PIÑA 473ML	\N	t
945	11	1	CR946	\N	NUTRI SNACKS PALITOS DE OLIVA Y AMAPOLA 100G	\N	t
946	11	1	CR947	\N	NUTRI SNACKS PALITOS DE AJONJOLI 100G	\N	t
947	11	1	CR948	\N	NUTRI SNACKS ROSQUILLAS DE MAIZ Y QUESO 100G	\N	t
948	11	1	CR949	\N	CAPPY INFLADITOS QUESO Y MANTEQUILLA 16G	\N	t
949	11	1	CR950	\N	CAPPY INFLADITOS PICOSITOS 16G	\N	t
950	11	1	CR951	\N	ZIBBAS CLASIC 80G	\N	t
951	11	1	CR952	\N	ZIBBAS CREMA Y ESPECIES 40G	\N	t
952	6	1	CR953	\N	DORIVAL	\N	t
953	11	1	CR954	\N	NUTRI SNACKS ROSQUILLA QUESO CHEDDAR 100G	\N	t
954	11	1	CR955	\N	BIG MIX FUEGO 40G	\N	t
955	6	1	CR956	\N	MEZCLA TRIP PROTEINA VAINILLA 32G	\N	t
956	8	1	CR957	\N	DELEITE DE LIMON	\N	t
957	11	1	tips	\N	Tips	\N	t
958	11	1	CR958	\N	PACHANGA MIX 45G	\N	t
959	1	1	CR959	\N	MAX JALAPENA 41G	\N	t
960	11	1	CR960	\N	TACO BBQ 28G	\N	t
961	11	1	CR961	\N	TACO PICANTE 28G	\N	t
962	3	1	CR962	\N	GALLETA BRINKY VAINILLA 25G	\N	t
963	3	1	CR963	\N	GALLETA BRINKY MANTEQUILLA 26G	\N	t
964	3	1	CR964	\N	GALLETAS BRINKY LECHE 12G	\N	t
965	3	1	CR965	\N	GALLETAS BRINKY CHOCOLATE 25G	\N	t
966	11	1	CR966	\N	CEBOLLINOS PQ	\N	t
967	11	1	CR967	\N	Cebollinos  30G	\N	t
968	11	1	CR968	\N	POP GRAINNS QUESO 50G	\N	t
969	11	1	POP GRAINS PICANTE 50G	\N	POP GRAINS PICANTE 50G	\N	t
970	11	1	CR969	\N	POP GRAINS QUESO 50G	\N	t
971	11	1	CR970	\N	KIBO LENTEJAS 28G	\N	t
972	3	1	CR971	\N	SORBE-TICO FRESA 28G	\N	t
973	3	1	CR972	\N	CHIKY MUNDO SECRETO	\N	t
974	3	1	CR973	\N	SORBE-TICO CHOCOLATE 28G	\N	t
975	3	1	CR974	\N	COCANAS	\N	t
976	2	1	CR975	\N	CONFITES CONEJITO	\N	t
977	11	1	CR976	\N	GALLETA SUGAR FREE MANZANA Y VCANELA	\N	t
978	1	1	CR977	\N	TROPICAL CERO MANZANA VERDE  500ML	\N	t
979	11	1	CR978	\N	ROSQUILLA CRIOLLA 23G	\N	t
980	11	1	CR979	\N	LAKY MEN JAIBA CON LIMON 75G	\N	t
981	11	1	CR980	\N	POPI PALOMITAS DE QUESO 85G	\N	t
982	11	1	CR981	\N	POPI PALOMITAS DE MANTEQUILLA 85G	\N	t
983	11	1	\N	\N	STARBURST PEQUEÑO	Código Nova duplicado en el origen: CR983; pendiente de revisión	t
984	11	1	CR982	\N	STARBURST PEQUEÑO	\N	t
985	11	1	\N	\N	POP GRAINS PICANTE 50G	Código Nova duplicado en el origen: CR983; pendiente de revisión	t
986	11	1	CR984	\N	NARANJITAS CONFLES 25G	\N	t
987	11	1	CR985	\N	POFFIS DULCE LECHE CONFLES 25G	\N	t
988	11	1	CR986	\N	TRIJUELAS MIEL CONFLES 20G	\N	t
989	11	1	CR987	\N	RODITAS FRESA CONFLES 25G	\N	t
990	11	1	CR988	\N	CHOKO CONFLES 25G	\N	t
991	2	1	CR989	\N	SAPITOS CHOCOLATE 10G	\N	t
992	11	1	CR990	\N	BARQUILLOS CHOCOLATE 65G	\N	t
993	2	1	CR991	\N	CHOYS ROSCA 31G	\N	t
994	3	1	CR992	\N	BOKITAS FUSION CHOCOLATE	\N	t
995	2	1	CR993	\N	MILKA DAIM 100G	\N	t
996	2	1	CR994	\N	ALFAJOR BON BOM CLARO 40G	\N	t
997	3	1	CR995	\N	MANTEQUILLA CHOCOLATE	\N	t
998	2	1	CR996	\N	TRIDENT AZUL PQ 5.2G	\N	t
999	11	1	CR997	\N	TRIDENT `PEQ VERDE 5.2G	\N	t
1000	2	1	CR998	\N	BON O BON CLARO 15G	\N	t
1001	11	1	CR999	\N	BARRA FRUTAL MANZANA ROJA 27G	\N	t
1002	11	1	CR1000	\N	BARRA FRUTAL MANZANA VERDE 27G	\N	t
1003	4	1	CR1001	\N	BARRA PODER TRIGANOLA 23G	\N	t
1004	11	1	CR1002	\N	BARRA PODER COCO 22G	\N	t
1005	1	1	CR1003	\N	TROPICAL MANZANA 350ML	\N	t
1006	2	1	CR1004	\N	TUTU TRUE LOVERS 80G	\N	t
1007	2	1	CR1005	\N	TUTU BAILYS 42G	\N	t
1008	2	1	CR1006	\N	BON BON DE TUTU	\N	t
1009	2	1	CR1007	\N	TUTTO DE VAINILLA  80G	\N	t
1010	2	1	CR1008	\N	TUTTO DE CARAMELO 80G	\N	t
1011	11	1	CR1009	\N	PALITOS DE GIRASOL SEED 50G	\N	t
1012	11	1	CR1010	\N	PALITOS  DE AJONJOLI SWEET SEED 50G	\N	t
1013	11	1	CR1011	\N	PALITOS DE AJONJOLI ORIGINALES SEED 50G	\N	t
1014	11	1	CR1012	\N	PALITOS DE ACEITE CON OLIVA 50G	\N	t
1015	1	1	CR1013	\N	PRINGLES SOUR CREAM & ONION 40G	\N	t
1016	11	1	CR1014	\N	PRINGLES ORIGINAL 37G	\N	t
1017	5	1	CR1015	\N	BISCOLITE ROSQUILLAS MAÍZ Y QUESO 100G	\N	t
1018	11	1	CR1016	\N	BARRA KELLGG'S NUTRI GRAIN ARANDANO 37G	\N	t
1019	11	1	CR1017	\N	BARRA KELLGG'S NUTRI GRAIN FRESA 37G	\N	t
1020	11	1	CR1018	\N	BARRA KELLGG'S NUTRI GRAIN MANZANA CANELA 37G	\N	t
1021	11	1	CR1019	\N	BARRA NATURE VALLEY PROTEINA PEANUT BUTTER 40G	\N	t
1022	11	1	CR1020	\N	BARRA NATURE VALLEY FRUIT & NUT 35G	\N	t
1023	2	1	CR1021	\N	TRULULU GOMITAS NANOS 80G	\N	t
1024	2	1	CR1022	\N	TRULULU CHOCOLORES GELATINA 30G	\N	t
1025	1	1	CR1023	\N	YOGURT GRIEGO PLUS COCO 200ML	\N	t
1026	1	1	CR1024	\N	HELADO DELEITE CREME BRULÉE 170G	\N	t
1027	11	1	CR1025	\N	TAKIS BLUE HEAT 56G	\N	t
1028	11	1	CR1026	\N	TAKIS HUAKAMOLES 56G	\N	t
1029	11	1	CR1027	\N	TAKIS INTENSE NACHO 56G	\N	t
1030	4	1	CR1028	\N	CHIPS SAL 36G	\N	t
1031	4	1	CR1029	\N	CHIPS FUEGO 36G	\N	t
1032	11	1	CR1030	\N	CHIKY BOING 72G	\N	t
1033	1	1	CR1031	\N	JUGO DE MANGO DEL VALLE 200ML	\N	t
1034	1	1	CR1032	\N	JUGO DE MANZANA DEL VALLE 200ML	\N	t
1035	1	1	CR1033	\N	JUGO DE GUAYABA DEL VALLE 200ML	\N	t
1036	1	1	CR1034	\N	JUGO DE NARANJA DEL VALLE 200ML	\N	t
1037	10	1	CR1035	\N	MANÍ PRO JAPANESE CRUNCH 80G	\N	t
1038	6	1	CR1036	\N	ALKA-AD	\N	t
1039	6	1	CR1037	\N	ALIVE 220MG	\N	t
1040	11	1	CR1038	\N	NS PALITOS DE QUESO PICANTE KETO 30G	\N	t
1041	8	1	CR1039	\N	YOGURT GRIEGO PLUS VAINILLA 200G	\N	t
1042	8	1	CR1040	\N	YOGURTGRIEGO TRIPLE PROTEINAFRESA 150G	\N	t
1043	8	1	CR1041	\N	YOGURT GRIEGO TRIPLEPROTEINA ANARANDANOS 150G	\N	t
1044	1	1	CR1042	\N	Big Cola 1.75L	\N	t
1045	1	1	CR1043	\N	Fanta Naranja 1.5L	\N	t
1046	2	1	CR1044	\N	MILKA AVELLANAS 90G	\N	t
1047	2	1	CR1045	\N	MILKA AVELLAS CON PASAS 90G	\N	t
1048	11	1	CR1046	\N	ELOTITOS CON LIMÓN 21G	\N	t
1049	11	1	CR1047	\N	ELOTITOS BBQ 21G	\N	t
1050	11	1	CR1048	\N	PALITOS CHILIMON 23G	\N	t
1051	11	1	CR1049	\N	TOZTECAS ORIGINAL	\N	t
1052	2	1	CR1050	\N	CADY MAÍZ 15G	\N	t
1053	11	1	CR1051	\N	QUESITOS DIANA 15G	\N	t
1054	11	1	CR1052	\N	CENTAVITOS DIANA 15G	\N	t
1055	11	1	CR1053	\N	ALBOROTO 26G	\N	t
1056	11	1	CR1054	\N	JALAPEÑOS DIANA 23G	\N	t
1057	11	1	CR1055	\N	ELOTITOS CON PiQUETES	\N	t
1058	11	1	CR1056	\N	CHURRITOS 15G	\N	t
1059	1	1	CR1057	\N	BIG COLA NARANJA 250ML	\N	t
1060	11	1	CR1058	\N	ELECTROLIFE ZERO MORA AZUL 625ML	\N	t
1061	1	1	CR1059	\N	BIG COLA 1L	\N	t
1062	1	1	CR1060	\N	BIG  FRESA 250ML	\N	t
1063	11	1	CR1061	\N	BIG GINCER ALE 250ML	\N	t
1064	11	1	CR1062	\N	BIG TORONJA 250 ML	\N	t
1065	11	1	CR1063	\N	PRIME BLUE CHILL 500ML	\N	t
1066	11	1	CR1064	\N	PRIME BERRY FREEZE	\N	t
1067	11	1	CR1065	\N	ATUN SPLASH TROZOS	\N	t
1068	11	1	CR1066	\N	ATUN SPLASH VEGETALES 140 grs	\N	t
1069	11	1	CR1067	\N	ATUN SPLASH  AHUMADO 105GRS	\N	t
1070	11	1	CR1068	\N	ATUN SPLASH TROCITOS AGUA	\N	t
1071	11	1	CR1069	\N	ATUN SPLASH JALAPEÑOS	\N	t
1072	11	1	\N	\N	CHIKY MUNDO SECRETO	Código Nova en conflicto en el origen: CR1070	t
1073	11	1	CR1070	\N	Fanta Naranja 2.5L	\N	t
1074	11	1	CR1071	\N	MOGUL MORAS	\N	t
1075	11	1	CR1072	\N	MOGUL OSO EXTREME	\N	t
1076	11	1	CR1073	\N	MOGUL SANDIA	\N	t
1077	11	1	CR1074	\N	LACICERO AZUL	\N	t
1078	11	1	CR1075	\N	LAPICERO NEGRO BIC	\N	t
1079	11	1	CR1076	\N	CUCHARA PLASTICA	\N	t
1080	11	1	CR1077	\N	TENEDOR PLASTICO	\N	t
1081	11	1	CR1078	\N	DOS PINOS NECTAR FRUTAS 200ML	\N	t
1082	1	1	CR1079	\N	SODA HATSU FRAMBUESA 300ML	\N	t
1083	1	1	CR1080	\N	SODA HATSU SANDIA 300ML	\N	t
1084	11	1	CR1081	\N	CHOKOLITO FRESA  560G	\N	t
1085	11	1	CR1082	\N	PICARITA JALAPEÑA 27G	\N	t
1086	11	1	CR1083	\N	TAQUERITOS DRAGON FUEGO 80G	\N	t
1087	11	1	CR1084	\N	TAQUERITOS DRAGON AZUL 80G	\N	t
1088	1	1	CR1085	\N	PRIME- CHERRY FREEZE 500ml	\N	t
1089	1	1	CR1086	\N	SUEROX MORA AZUL 630ML	\N	t
1090	1	1	CR1087	\N	SUEROX- MANZANA 630ML	\N	t
1091	1	1	CR1088	\N	ELECTROLIFE ZERO - PONCHE  FRUTAS 625ML	\N	t
1092	1	1	CR1089	\N	ELECTROLIFE ZERO - FRAMBUESA AZUL 625ML	\N	t
1093	1	1	CR1090	\N	ELECTROLIT - MORA AZUL 625ML	\N	t
1094	1	1	CR1100	\N	ELECTROLIFE - FRESA KIWI 625ML	\N	t
1095	1	1	CR1091	\N	ELECTROLIT - FRESA KIWI 625ML	\N	t
1096	1	1	CR1092	\N	COCA COLA 1.5L	\N	t
1097	11	1	CR1093	\N	PAJILLAS BIOPAPEL	\N	t
1098	5	1	CR1094	\N	TODAY DONA CHOCOLATE	\N	t
1099	11	1	CR1095	\N	Jet 250ml	\N	t
1100	11	1	CR1096	\N	TODAY DONA FRESA	\N	t
1101	3	1	CR1097	\N	SPONCH PIÑA	\N	t
1102	11	1	CR1098	\N	Chicharrones   autenticos 100g	\N	t
1103	11	1	CR1099	\N	RANCHO CHICHARRONES PICOSITOS	\N	t
1104	11	1	CR1200	\N	ALASKA NARANJA PIÑA	\N	t
1105	7	1	CR1201	\N	ALASKA LECHE CONDENSADA	\N	t
1106	11	1	CR1202	\N	JACKS BARRA PODER	\N	t
1107	11	1	CR1203	\N	JACKS FRUTTAL MANGO	\N	t
1108	11	1	CR1204	\N	JACKS BARRA DULCE DE LECHE	\N	t
1109	11	1	CR1205	\N	ZAMBOS CEVICHE	\N	t
1110	11	1	CR1206	\N	ZAMBOS CON CHICHARRON	\N	t
1111	3	1	CR1207	\N	JACKS CHOCOCHIPAS BARRA	\N	t
1112	1	1	CR1208	\N	Tropical Frutas Mixtas 1L	\N	t
1113	1	1	CR1209	\N	Tropical Melocoton 1L	\N	t
1114	1	1	CR1210	\N	Tropical te blanco arandano 1L	\N	t
1115	4	1	CR1211	\N	Ranchitas Buffalo Rach	\N	t
1116	4	1	CR1212	\N	Yummi pops Nacho Queso Blanco	\N	t
1117	4	1	CR1214	\N	Ziba's miel mostaza 80g	\N	t
1118	6	1	CR1215	\N	SUPER GLUE	\N	t
1119	6	1	CR1216	\N	GILLETE PRESTOBARBA	\N	t
1120	4	1	CR1217	\N	MEJITOS MEDIANOS	\N	t
1121	2	1	CR1218	\N	ORIGAMI ARANDANOS	\N	t
1122	11	1	CR1219	\N	ORIGAMI FRUTOS AMARILLAS	\N	t
1123	2	1	CR1220	\N	BIANCHI CARAMELO	\N	t
1124	1	1	CR1221	\N	ALMA SPARKLING UVA FRAMBUESA	\N	t
1125	11	1	CR1222	\N	MOGUL JELLY BEANS	\N	t
1126	3	1	CR1223	\N	CHIKY CHIPS	\N	t
1127	11	1	CR1224	\N	CREMAS DARK	\N	t
1128	11	1	049000056433	\N	POWERADE ZERO FRUIT PUNCH	\N	t
1129	1	1	CR1225	\N	Powerade ZERO FRUIT	\N	t
1130	11	1	CR1226	\N	TROPICAL TE ROJO PITAHAYA	\N	t
1131	3	1	CR1227	\N	TROPICAL TE BLANCO NOPAL	\N	t
1132	4	1	CR1228	\N	Yummis  Pops dragon hielo	\N	t
1133	11	1	CR1229	\N	ZIBAS CHILE TOREADO 23G	\N	t
1134	11	1	CR1230	\N	Mento plus cherry 30.6g	\N	t
1135	11	1	CR1232	\N	Mento plus menthol 30g	\N	t
1136	2	1	CR1233	\N	Mento plus strong	\N	t
1137	11	1	CR1231	\N	Menthosplus duo naranja	\N	t
1138	2	1	CR1234	\N	Menthosplus duo manzana	\N	t
1139	2	1	CR1235	\N	ZORRITON	\N	t
1140	9	1	CR1236	\N	SOPA LAKY MEN SOBRE CAMARON	\N	t
1141	9	1	CR1237	\N	SOPA LAKY SOBRE POLLO	\N	t
1142	9	1	CR1238	\N	SOPA LAKY SOBRE RES	\N	t
1143	1	1	CR1239	\N	MAXI MALTA ORIGINAL 350ml	\N	t
1144	1	1	CR1240	\N	Red bull 250ml	\N	t
1145	1	1	CR1241	\N	Red bull 250ml sin azucar	\N	t
1146	2	1	CR1242	\N	LifeSavers hard 5 Flav	\N	t
1147	2	1	CR1243	\N	CORAZONES DE CHOCOLATE	\N	t
1148	11	1	CR1244	\N	Chocolate leche pinito	\N	t
1149	2	1	CR1245	\N	Chocolate Rush barra de mini	\N	t
1150	11	1	CR1246	\N	Chocolate Rush Caramelo	\N	t
1151	11	1	CR1248	\N	Confites Menta Gallito	\N	t
1152	11	1	CR1247	\N	Chocolate tapita navideña	\N	t
1153	11	1	CR1250	\N	Tosh Original	\N	t
1154	7	1	CR1251	\N	KRUNCHY ROMPOPE	\N	t
1155	2	1	CR1252	\N	CHOCOBOLAS	\N	t
1156	3	1	CR1253	\N	CHIKY CHOCONIEVES	\N	t
1157	4	1	CR1254	\N	NUCITAS MARSHMALLOW SPIDERS	\N	t
1158	4	1	CR1255	\N	Kryzpo Cebolla	\N	t
1159	4	1	CR1256	\N	Kryzpo queso	\N	t
1160	4	1	CR1257	\N	Kryzpo  queso	\N	t
1161	2	1	CR1258	\N	Violeta	\N	t
1162	11	1	CR1259	\N	Morenito	\N	t
1163	11	1	CR1260	\N	Fijador EGO	\N	t
1164	6	1	CR1261	\N	ASPIRINA	\N	t
1165	6	1	CR1262	\N	Tiamina	\N	t
1166	3	1	CR1263	\N	Galleta MAria	\N	t
1167	1	1	CR1264	\N	Max Energy Solar Mango	\N	t
1168	1	1	CR1265	\N	Max energy apple kiwi	\N	t
1169	1	1	CR1266	\N	Max energy berry crush	\N	t
1170	1	1	CR1267	\N	Max sandia bite	\N	t
1171	1	1	CR1268	\N	Fanta Strawberry Lata 353ml	\N	t
1172	1	1	CR1269	\N	7UP Tropical 355ml	\N	t
1173	1	1	CR1270	\N	Sparkling Ice Kiwi Strawberry 222ml	\N	t
1174	1	1	CR1271	\N	Sparkling fruit punch 222ml	\N	t
1175	1	1	CR1272	\N	Sparkling  Mandarina 222ml	\N	t
1176	11	1	CR1273	\N	Chicharron Salsa Verde 22G	\N	t
1177	4	1	CR1274	\N	Cantarin Jalapeno 100G	\N	t
1178	11	1	CR1275	\N	Zambos Camote 30G	\N	t
1179	11	1	CR1276	\N	Zambos Platano con Chicharron 33G	\N	t
1180	11	1	CR1277	\N	Zambos Platano Picositas 33G	\N	t
1181	11	1	CR1278	\N	Zambos con Tajin 33G	\N	t
1182	11	1	CR1279	\N	Big Mix Fuego 200G	\N	t
1183	11	1	CR1280	\N	Purejoy Trozos Mango Crujiente 14G	\N	t
1184	2	1	CR1281	\N	MINI SANDWICH CHOCO MENTA	\N	t
1185	11	1	CR1282	\N	Purejoy Trozos Pina Crujiente 14G	\N	t
1186	11	1	CR1283	\N	Chokis Chocobase 73,5G	\N	t
1187	11	1	CR1285	\N	Pronol 220MG	\N	t
1188	2	1	CR1286	\N	Bianchi Doble Chocolate 22G	\N	t
1189	2	1	CR1287	\N	Barra Guayabita	\N	t
1190	11	1	CR1288	\N	Album PANINI	\N	t
1191	11	1	CR1289	\N	Postales Panini	\N	t
1192	7	1	CR1300	\N	Helado Pinito	\N	t
1193	4	1	CR1301	\N	Ranchita Buffalo Ranch 70g	\N	t
1194	4	1	CR1302	\N	Taquerito Lava  34g	\N	t
1195	4	1	CR1303	\N	Twister Ardiente 75g	\N	t
1196	2	1	CR1304	\N	Choys Blanco	\N	t
1197	2	1	CR1305	\N	Super Coco Barra 40g	\N	t
1198	3	1	CR1306	\N	Galleta de Arroz Pie de Limon 25g	\N	t
1199	3	1	CR1308	\N	Galleta Cobert de Chocolate 25g	\N	t
1200	3	1	CR13209	\N	Barra de Proteina Chocolate y mani 35g	\N	t
1201	6	1	CR1310	\N	Prestobarva Gillete	\N	t
1202	2	1	CR1311	\N	Totto Choco Lovers 80g	\N	t
1203	2	1	CR1312	\N	TUTTO POPS BROWNIE	\N	t
1204	2	1	CT1313	\N	TUTTO POPS PIE DE LIMON	\N	t
1205	6	1	CR1314	\N	VAPES HEAVY  WEIGHT	\N	t
1206	6	1	CR1315	\N	DUMMY VAPES	\N	t
1207	2	1	CR1190	\N	Taqueritos Buffalo Ranch 34g	\N	t
1208	4	1	CR1191	\N	Zibas Crema y Especies 80g	\N	t
1209	2	1	CR1192	\N	TABCIN EXTRA FUERTE DIA	\N	t
1210	2	1	CR1193	\N	TABCIN EXTRA FUERTE NOCHE	\N	t
1211	4	1	CR1194	\N	HOT TWISTERS 75G	\N	t
1212	2	1	CR1195	\N	DEL RANCHO CHICHARRONES SALSA VERDE 100G	\N	t
1213	1	1	CR1196	\N	Maxxx Energy Guaraná Zero 473ml	\N	t
1214	1	1	CR1197	\N	SUEROX UVA  630ml	\N	t
1215	2	1	CR1198	\N	GATORADE LEMON LIME 600ML	\N	t
1216	1	1	CR1199	\N	TROPICAL LIMÓN 1L	\N	t
1217	1	1	CR1120	\N	TROPICAL MANZANA VERDE 350ML	\N	t
1218	1	1	CR1121	\N	RED BULL GREEN LATA 250ML	\N	t
1219	1	1	CR1122	\N	SPARKLING ICE LEMONADE LATA 222ML	\N	t
1220	2	1	CR1124	\N	POROPOZ 20G	\N	t
1221	4	1	CR1125	\N	Fusión Mix 20g	\N	t
1222	2	1	CR1126	\N	ZORRITONE	\N	t
1223	2	1	CR1127	\N	RED BULL 355ML	\N	t
1224	5	1	CR1316	7441053500049	Tamal Asado 240 grs (Sin gluten)	\N	t
1225	5	1	CR1317	7441053500032	Rosquillas de Maiz 125 grs (Sin gluten)	\N	t
1226	5	1	CR1318	7441053500070	Bizcochitos de Maiz 100grs (Sin gluten)	\N	t
1227	5	1	CR1319	7441053500131	Galletitas de Maiz 200grs (Sin gluten)	\N	t
1228	5	1	CR1320	7445064010146	Galletitas de Maiz con Naranja sin azucar 75grs (Sin gluten)	\N	t
1229	5	1	CR1321	7441053500285	Galletitas de Maiz con Coco sin azucar 75grs (Sin gluten)	\N	t
1230	5	1	CR1322	7441053500078	Galletitas de Maiz con Cacao sin azucar 75grs (Sin gluten)	\N	t
1231	4	1	CR1323	\N	Granuts Mezcla Nueces 45g	\N	t
1232	4	1	CR1324	\N	Granuts Mezcla Arandanos 45g	\N	t
1233	1	1	CR1325	\N	Refresco Mas Citrus Naranja 500ml	\N	t
1234	3	1	CR1326	\N	Pozuelo Wafer Baileys	\N	t
1235	2	1	CR1327	\N	VEGIS 60G	\N	t
1236	2	1	CR1328	\N	OLEADAS SALADITAS 27G	\N	t
1237	2	1	CR1329	\N	CHURROS CRUNCH 20G	\N	t
1238	2	1	CR1330	\N	POP GRAINS FINAS HIERBAS 50G	\N	t
1239	2	1	CR1331	\N	POP GRAINS SAL MARINA 50G	\N	t
1240	2	1	CR1332	\N	POP GRAINS DULCE SALADO 50G	\N	t
1241	2	1	CR1333	\N	YUMMI NUTS MANÍ CON SAL 80G	\N	t
1242	2	1	CR11334	\N	YUMMI NUTS MARAÑON SAL 25G	\N	t
1243	2	1	CR1335	\N	YUMMI NUTS MANÍ JAPONÉS 20G	\N	t
1244	2	1	CR1336	\N	CHOCOLATE MILÁN MENTA 35G	\N	t
1245	2	1	CR1337	\N	CANTON NOODLES MARISCOS 65G	\N	t
1246	2	1	CR1338	\N	CANTON NOODLES POLLO 65G	\N	t
1247	2	1	CR1339	\N	CANTON NOODLES CARNE 65G	\N	t
1248	2	1	CR1340	\N	TAKIS CHILE LIMÓN 49G	\N	t
1249	2	1	CR1341	\N	SHAMPOO PANTENE REPARADOR SIN SAL 10ML	\N	t
1250	2	1	CR1342	\N	TAQUERITOS QUESO FUSIÓN 75G	\N	t
1251	2	1	CR1343	\N	TAQUERITOS  CHILE TOREADO 75G	\N	t
1252	2	1	CR1344	\N	TAQUERITOS BUFFALO RANCH 75G	\N	t
1253	1	1	CR1345	\N	Arizona Iced tea  340ml	\N	t
1254	2	1	CR1346	\N	VIZZIO 21g	\N	t
1255	4	1	CR1347	\N	Pro pasa con chocolate  70g	\N	t
1256	4	1	CR1348	\N	Pro Arandano con chocolate 70g	\N	t
1257	2	1	CR1349	\N	Snickers bites	\N	t
1258	4	1	CR1350	\N	Pro Platano maduro con sal	\N	t
1259	4	1	CR1351	\N	Pro Platano con chicharron 90g	\N	t
1260	4	1	CR1352	\N	Pro Platano con miel de abeja	\N	t
1261	3	1	CR1353	\N	Sorbeto pinito	\N	t
1262	2	1	cr1354	\N	Tableta  Tapita Gallito 150g	\N	t
1263	2	1	CR1355	\N	Choco Gallito Tapita Fut	\N	t
1264	7	1	CR1356	\N	Helado de Pal Alaska choco banano	\N	t
1265	7	1	CR1357	\N	Mini sundae Alaska	\N	t
1266	2	1	CR1358	\N	CHOYS WAFER 28G	\N	t
1267	2	1	CR1359	\N	TRULULU NANOS 70G	\N	t
1268	2	1	CR1360	\N	TIC TAC FRUTS 14.5G	\N	t
1269	2	1	CR1361	\N	TIC TAC FRESA 14.5G	\N	t
1270	2	1	CR1362	\N	TIC TAC MENTA 14.5G	\N	t
1271	5	1	CR1363	\N	Queso crema Original 100g	\N	t
1272	5	1	CR1364	\N	Lactocrema 210g	\N	t
1273	5	1	CR1365	\N	Zarcero Natilla con sal 115g	\N	t
1274	5	1	CR1366	\N	Natilla 115g	\N	t
1275	1	1	CR1367	\N	Arizona te verdr & ginseng miel 650 ml	\N	t
1276	1	1	CR1368	\N	Arizona Kiwi- Fresa 650 ml	\N	t
1277	1	1	CR1369	\N	Arizona Te limondana fresa 650 ml	\N	t
1278	2	1	CR1370	\N	TRIDENT CANELA	\N	t
1279	2	1	CR1371	\N	ARIZONA SANDIA  650ML	\N	t
1280	2	1	CR1372	\N	CAJETAS	\N	t
1281	2	1	CR1373	\N	KINDER BUENO 43G	\N	t
1282	2	1	CR1374	\N	SORBETOS FRESA 23.5G	\N	t
1283	2	1	CR1375	\N	AVENY BRAN 67.8G	\N	t
1284	2	1	CR1376	\N	FLORESTAL HORTELA 31G	\N	t
1285	2	1	CR1377	\N	FLORESTAL CEREZA 32G	\N	t
1286	2	1	CR1378	\N	FLORESTAL TUTTI FRITTI 31G	\N	t
1287	2	1	CR1379	\N	FLORESTAL MENTA 31G	\N	t
1288	2	1	CR1380	\N	FLORESTAL FRUTAS 31G	\N	t
1289	2	1	CR13.81	\N	FLORESTAL YOGURT 31G	\N	t
1290	2	1	CR1382	\N	BON O BON COCO	\N	t
1291	2	1	CR1383	\N	BON O BON FRUTAS	\N	t
1292	2	1	CR1384	\N	ALASKA CHOCO PALETA 60G	\N	t
1293	2	1	CR1385	\N	CANADA DRY  ZERO 600ML	\N	t
1294	2	1	CR1386	\N	MENTOS 29G	\N	t
1295	2	1	CR1387	\N	BIG CITRUS NARANJA LIMON 250ML	\N	t
1296	2	1	CR1388	\N	TROPICAL MELOCOTON CERO 500 ML	\N	t
1297	2	1	CR1389	\N	PRO PLATANITOS MIEL DE ABEJA 65G	\N	t
1298	2	1	CR1390	\N	RICHLY  CHILES JALAPEÑOS 160G	\N	t
1299	2	1	CR1391	\N	TRICOPILIA GUAYABA SUGAR FREE	\N	t
1300	2	1	CR1392	\N	ENCENDEDOR WIDER	\N	t
1301	2	1	CR1393	\N	GATORADE UVA 600ML	\N	t
1302	2	1	CR1394	\N	BEBIDA PRIME FRESA SANDIA 500ML	\N	t
1303	2	1	CR1395	\N	ALOE LIGHT 500 ML	\N	t
1304	2	1	CR1396	\N	TE VERDE CERO 500 ML	\N	t
1305	2	1	CR1397	\N	TE BLANCO CERO MARACUYÁ 500ML	\N	t
1306	2	1	CR1398	\N	SPARKLING ICE TORONJA ROSA 502.8ML	\N	t
1307	2	1	CR1399	\N	SPARKLING ICE FRESA KIWI 502.8ML	\N	t
1308	2	1	CE1400	\N	SPARKLING ICE UVA FRAMBUESA 502.8ML	\N	t
1309	2	1	CR1401	\N	RAPTOR  LATA SANDIA 473ML	\N	t
1310	2	1	CR1402	\N	MAXXX ENERGY FRESA 350ML	\N	t
1311	2	1	CR1403	\N	GINGER ALE CHERRY LATA 355ML	\N	t
1312	2	1	CR1404	\N	GINGER ALE MORA LATA 355ML	\N	t
1313	2	1	CR1405	\N	BIG COLA UVA 250ML	\N	t
1314	2	1	CR1406	\N	TIC TAC NARANJA	\N	t
1315	2	1	CR1407	\N	TIC TAC CITRUS MIX	\N	t
1316	2	1	CR1408	\N	FLORESTAL GOMAS 150G	\N	t
1317	2	1	CR1409	\N	ALASKA PALETA CHOCOLATE 75G	\N	t
1318	2	1	CR1410	\N	VIAJESAN ANTIEMETICO	\N	t
1319	2	1	CR1411	\N	ANTIFLUDES MÁS	\N	t
1320	2	1	CR1412	\N	PANADOL MUJER	\N	t
1321	2	1	CR1413	\N	ACETAMINOFÉN 500ML	\N	t
1322	2	1	CR1414	\N	GALLETA CLUB EXTRA CANELA 25G	\N	t
1323	2	1	CR1415	\N	CHAO MENTA 30G	\N	t
1324	2	1	CR1416	\N	GALLETAS CHIPS AHOY	\N	t
1325	2	1	CR1417	\N	CREMA PARA PEINAR PANTENE 9ML	\N	t
1326	2	1	CR1418	\N	CREMA PANTENE RISOS 9ML	\N	t
1327	2	1	CR1419	\N	DORATIDAS 45G	\N	t
1328	11	1	\N	\N	Zambos Originales 70g	Agregado desde revisión de precios; código Nova pendiente.	t
1329	11	1	\N	\N	YUMMINUTS	Agregado desde revisión de precios; código Nova pendiente.	t
1330	11	1	\N	\N	Mani Japones  con chile	Agregado desde revisión de precios; código Nova pendiente.	t
1331	11	1	\N	\N	Maxxx energy  guarana 350ml	Agregado desde revisión de precios; código Nova pendiente.	t
1332	11	1	\N	\N	max  energy peach mango	Agregado desde revisión de precios; código Nova pendiente.	t
1333	11	1	\N	\N	tripocal	Agregado desde revisión de precios; código Nova pendiente.	t
1334	11	1	\N	\N	TOCINITOS LIMON 19G	Agregado desde revisión de precios; código Nova pendiente.	t
1335	11	1	\N	\N	ELECTROLIT  NARANJA- MANDARINA 625ML	Agregado desde revisión de precios; código Nova pendiente.	t
1336	11	1	\N	\N	ELECTROLIT MANZANA 625ML	Agregado desde revisión de precios; código Nova pendiente.	t
1337	11	1	\N	\N	ELECTROLIT LIMA/LIMON 625ML	Agregado desde revisión de precios; código Nova pendiente.	t
1338	11	1	\N	\N	CHOCOLATE CRISPI	Agregado desde revisión de precios; código Nova pendiente.	t
1339	11	1	\N	\N	TODAY WAFER CHOCOLATE	Agregado desde revisión de precios; código Nova pendiente.	t
1340	11	1	\N	\N	Fanta fresa	Agregado desde revisión de precios; código Nova pendiente.	t
1341	11	1	\N	\N	Sparkling ICE Ponche de Frutas 221,8ml	Agregado desde revisión de precios; código Nova pendiente.	t
1342	11	1	\N	\N	Barra de Proteina Arandanos 35 g	Agregado desde revisión de precios; código Nova pendiente.	t
1343	11	1	\N	\N	Baunfly Brownie Caja Alfajor	Agregado desde revisión de precios; código Nova pendiente.	t
1344	11	1	\N	\N	Baunfly mini Alfajor	Agregado desde revisión de precios; código Nova pendiente.	t
1345	11	1	\N	\N	Freegells Menta 27,9	Agregado desde revisión de precios; código Nova pendiente.	t
1346	11	1	\N	\N	Freegells Miel 27,9	Agregado desde revisión de precios; código Nova pendiente.	t
1347	11	1	\N	\N	Milka luflee 100g	Agregado desde revisión de precios; código Nova pendiente.	t
1348	11	1	\N	\N	Poppy Barrite	Agregado desde revisión de precios; código Nova pendiente.	t
1349	11	1	\N	\N	Rossana Bolsa 127g	Agregado desde revisión de precios; código Nova pendiente.	t
1350	11	1	\N	\N	Rossana Bolsa 194g	Agregado desde revisión de precios; código Nova pendiente.	t
1351	11	1	\N	\N	Soldanza platano queso chile 42g	Agregado desde revisión de precios; código Nova pendiente.	t
1352	11	1	\N	\N	Trindent Miel 5 UNIDADES	Agregado desde revisión de precios; código Nova pendiente.	t
1353	11	1	\N	\N	Chao sandia	Agregado desde revisión de precios; código Nova pendiente.	t
1354	11	1	\N	\N	N.S. Lonchera Gluten Free 12u	Agregado desde revisión de precios; código Nova pendiente.	t
1355	11	1	\N	\N	Galleta de Arroz Cobertura Yogurt 25g	Agregado desde revisión de precios; código Nova pendiente.	t
1356	11	1	\N	\N	Galleta nutri snack Galleta dulce	Agregado desde revisión de precios; código Nova pendiente.	t
1357	11	1	\N	\N	Trists Vet. Chocolate	Agregado desde revisión de precios; código Nova pendiente.	t
1358	11	1	\N	\N	Yogurt Delactomy fresa 200ml	Agregado desde revisión de precios; código Nova pendiente.	t
1359	11	1	\N	\N	Deligurt Toppin coco- cacao	Agregado desde revisión de precios; código Nova pendiente.	t
1360	11	1	\N	\N	Deligurt de pie de limon	Agregado desde revisión de precios; código Nova pendiente.	t
1361	11	1	\N	\N	Te blanco Frutos fibra 500ml	Agregado desde revisión de precios; código Nova pendiente.	t
1362	11	1	\N	\N	Te verde Antioxidante 500ml	Agregado desde revisión de precios; código Nova pendiente.	t
1363	11	1	\N	\N	Cremolata	Agregado desde revisión de precios; código Nova pendiente.	t
1364	11	1	\N	\N	Jugo naranja 1 litro	Agregado desde revisión de precios; código Nova pendiente.	t
1365	11	1	\N	\N	Bebida Viking 269 ml	Agregado desde revisión de precios; código Nova pendiente.	t
1366	11	1	\N	\N	confite fresa	Agregado desde revisión de precios; código Nova pendiente.	t
1367	11	1	\N	\N	Tableta de Guayaba	Agregado desde revisión de precios; código Nova pendiente.	t
1368	11	1	\N	\N	Sparkin Pit/HB 355 ML	Agregado desde revisión de precios; código Nova pendiente.	t
1369	11	1	\N	\N	papa dos pinos	Agregado desde revisión de precios; código Nova pendiente.	t
1370	11	1	\N	\N	Principe Vainilla 85g	Agregado desde revisión de precios; código Nova pendiente.	t
1371	11	1	\N	\N	kronk vainilla	Agregado desde revisión de precios; código Nova pendiente.	t
1372	11	1	\N	\N	PrinTriCho	Agregado desde revisión de precios; código Nova pendiente.	t
1373	11	1	\N	\N	PrinCooCre	Agregado desde revisión de precios; código Nova pendiente.	t
1374	11	1	\N	\N	BizcMa	Agregado desde revisión de precios; código Nova pendiente.	t
1375	11	1	\N	\N	Takis Fuego 28g	Agregado desde revisión de precios; código Nova pendiente.	t
1376	11	1	\N	\N	Galleta Sanissimo	Agregado desde revisión de precios; código Nova pendiente.	t
1377	11	1	\N	\N	Big Kolita 250ml	Agregado desde revisión de precios; código Nova pendiente.	t
1378	11	1	\N	\N	Big Cola Jugo Cifrut Naranja 250ml	Agregado desde revisión de precios; código Nova pendiente.	t
1379	11	1	\N	\N	Galleta avena frutos rojos	Agregado desde revisión de precios; código Nova pendiente.	t
1380	11	1	\N	\N	Jalapenos 71g	Agregado desde revisión de precios; código Nova pendiente.	t
1381	11	1	\N	\N	Caja Barritas tosh	Agregado desde revisión de precios; código Nova pendiente.	t
1382	11	1	\N	\N	Cremas Limon	Agregado desde revisión de precios; código Nova pendiente.	t
1383	11	1	\N	\N	Galleta Yumbo Fibra y miel	Agregado desde revisión de precios; código Nova pendiente.	t
1384	11	1	\N	\N	Merendina Gatico	Agregado desde revisión de precios; código Nova pendiente.	t
1385	11	1	\N	\N	Nucita zombie	Agregado desde revisión de precios; código Nova pendiente.	t
1386	11	1	\N	\N	Tosh Miel	Agregado desde revisión de precios; código Nova pendiente.	t
1387	11	1	\N	\N	Ttto sin azucar 20g	Agregado desde revisión de precios; código Nova pendiente.	t
1388	11	1	\N	\N	TUTTO BONBONES RELLENOS	Agregado desde revisión de precios; código Nova pendiente.	t
1389	11	1	\N	\N	Tutto de Crispy Berries 80g	Agregado desde revisión de precios; código Nova pendiente.	t
1390	11	1	\N	\N	Wafer Festival Vainilla	Agregado desde revisión de precios; código Nova pendiente.	t
1391	11	1	\N	\N	Yumbo	Agregado desde revisión de precios; código Nova pendiente.	t
1392	11	1	\N	\N	Hi-C Uva 1 litro	Agregado desde revisión de precios; código Nova pendiente.	t
1393	11	1	\N	\N	PW Ver 591 ML	Agregado desde revisión de precios; código Nova pendiente.	t
1394	11	1	\N	\N	Arizona te blanco & ginseng 650ml	Agregado desde revisión de precios; código Nova pendiente.	t
1395	11	1	\N	\N	Sparking ice coco piña	Agregado desde revisión de precios; código Nova pendiente.	t
1396	11	1	\N	\N	Sparking ice sandia	Agregado desde revisión de precios; código Nova pendiente.	t
1397	11	1	\N	\N	Sparking ice lima limon	Agregado desde revisión de precios; código Nova pendiente.	t
1398	11	1	\N	\N	Sparking ice naranja mango	Agregado desde revisión de precios; código Nova pendiente.	t
1399	11	1	\N	\N	welchs jugo manzana 100% 295ml	Agregado desde revisión de precios; código Nova pendiente.	t
1400	11	1	\N	\N	Pro Platano verde salado 70.9g	Agregado desde revisión de precios; código Nova pendiente.	t
1401	11	1	\N	\N	Pro Platano verde limon sal 140G	Agregado desde revisión de precios; código Nova pendiente.	t
1402	11	1	\N	\N	Pro platanos Sabor Salsa lizano 120 G	Agregado desde revisión de precios; código Nova pendiente.	t
1403	11	1	\N	\N	Pro Platano salados en tiras 180g	Agregado desde revisión de precios; código Nova pendiente.	t
1404	11	1	\N	\N	Pro chicharron 70G	Agregado desde revisión de precios; código Nova pendiente.	t
1405	11	1	\N	\N	Pro chicharron con limon 70G	Agregado desde revisión de precios; código Nova pendiente.	t
1406	11	1	\N	\N	Pro Patacones con sal 150G	Agregado desde revisión de precios; código Nova pendiente.	t
1407	11	1	\N	\N	Snickers MINIS BOLSA	Agregado desde revisión de precios; código Nova pendiente.	t
1408	11	1	\N	\N	Snickers MINIS unidades	Agregado desde revisión de precios; código Nova pendiente.	t
1409	11	1	\N	\N	Raptor lava lata 473 ml	Agregado desde revisión de precios; código Nova pendiente.	t
1410	11	1	\N	\N	Raptor Relampago  lata 473ml	Agregado desde revisión de precios; código Nova pendiente.	t
1411	11	1	\N	\N	Especias canela molida MCCORNICK	Agregado desde revisión de precios; código Nova pendiente.	t
1412	11	1	\N	\N	Galleta gama cracker	Agregado desde revisión de precios; código Nova pendiente.	t
1413	11	1	\N	\N	Crema Nivea	Agregado desde revisión de precios; código Nova pendiente.	t
1414	11	1	\N	\N	Barra Granola Nutri Valley Chocolate 40g	Agregado desde revisión de precios; código Nova pendiente.	t
1415	11	1	\N	\N	Member's Selection Agua con Gas de Sabor a Frutas Cero Azúcar	Agregado desde revisión de precios; código Nova pendiente.	t
1416	11	1	\N	\N	Alfajor blanco	Agregado desde revisión de precios; código Nova pendiente.	t
1417	11	1	\N	\N	Chokos pequeños	Agregado desde revisión de precios; código Nova pendiente.	t
1418	11	1	\N	\N	Kinder Mini	Agregado desde revisión de precios; código Nova pendiente.	t
1419	9	1	\N	\N	Gallo pinto	Agregado desde revisión de precios; código Nova pendiente.	t
\.


--
-- Data for Name: producto_proveedor; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.producto_proveedor (id_producto_proveedor, id_producto, id_proveedor, costo, estado, fecha_actualizacion) FROM stdin;
1	5	3	663.00	t	2026-10-02 00:50:41.741214+00
2	8	11	663.71	t	2026-10-02 00:50:41.741214+00
3	9	11	663.71	t	2026-10-02 00:50:41.741214+00
4	10	11	663.71	t	2026-10-02 00:50:41.741214+00
5	11	11	663.71	t	2026-10-02 00:50:41.741214+00
6	11	12	593.87	t	2026-10-02 00:50:41.741214+00
7	12	11	663.71	t	2026-10-02 00:50:41.741214+00
8	21	13	654.89	t	2026-10-02 00:50:41.741214+00
9	26	11	1253.69	t	2026-10-02 00:50:41.741214+00
10	26	12	1185.64	t	2026-10-02 00:50:41.741214+00
11	28	11	1253.69	t	2026-10-02 00:50:41.741214+00
12	28	12	1185.64	t	2026-10-02 00:50:41.741214+00
13	29	11	368.73	t	2026-10-02 00:50:41.741214+00
14	30	11	368.73	t	2026-10-02 00:50:41.741214+00
15	30	12	331.85	t	2026-10-02 00:50:41.741214+00
16	31	11	368.73	t	2026-10-02 00:50:41.741214+00
17	31	12	331.85	t	2026-10-02 00:50:41.741214+00
18	32	11	294.98	t	2026-10-02 00:50:41.741214+00
19	32	12	331.85	t	2026-10-02 00:50:41.741214+00
20	33	11	368.73	t	2026-10-02 00:50:41.741214+00
21	33	12	331.85	t	2026-10-02 00:50:41.741214+00
22	34	11	368.73	t	2026-10-02 00:50:41.741214+00
23	34	12	331.85	t	2026-10-02 00:50:41.741214+00
24	35	11	294.99	t	2026-10-02 00:50:41.741214+00
25	37	11	294.99	t	2026-10-02 00:50:41.741214+00
26	41	11	405.61	t	2026-10-02 00:50:41.741214+00
27	42	11	663.71	t	2026-10-02 00:50:41.741214+00
28	42	12	593.87	t	2026-10-02 00:50:41.741214+00
29	48	5	776.00	t	2026-10-02 00:50:41.741214+00
30	54	5	330.00	t	2026-10-02 00:50:41.741214+00
31	59	5	602.73	t	2026-10-02 00:50:41.741214+00
32	63	5	322.00	t	2026-10-02 00:50:41.741214+00
33	64	5	634.63	t	2026-10-02 00:50:41.741214+00
34	65	7	228.98	t	2026-10-02 00:50:41.741214+00
35	65	15	221.24	t	2026-10-02 00:50:41.741214+00
36	66	7	229.22	t	2026-10-02 00:50:41.741214+00
37	66	15	221.24	t	2026-10-02 00:50:41.741214+00
38	72	7	219.92	t	2026-10-02 00:50:41.741214+00
39	73	7	219.92	t	2026-10-02 00:50:41.741214+00
40	83	11	368.73	t	2026-10-02 00:50:41.741214+00
41	83	12	331.85	t	2026-10-02 00:50:41.741214+00
42	112	6	1185.84	t	2026-10-02 00:50:41.741214+00
43	113	6	1415.93	t	2026-10-02 00:50:41.741214+00
44	118	1	184.37	t	2026-10-02 00:50:41.741214+00
45	119	1	200.00	t	2026-10-02 00:50:41.741214+00
46	130	4	366.66	t	2026-10-02 00:50:41.741214+00
47	149	10	149.08	t	2026-10-02 00:50:41.741214+00
48	150	10	147.10	t	2026-10-02 00:50:41.741214+00
49	154	10	142.10	t	2026-10-02 00:50:41.741214+00
50	155	10	142.10	t	2026-10-02 00:50:41.741214+00
51	156	10	138.33	t	2026-10-02 00:50:41.741214+00
52	160	10	194.69	t	2026-10-02 00:50:41.741214+00
53	161	10	194.69	t	2026-10-02 00:50:41.741214+00
54	175	7	134.69	t	2026-10-02 00:50:41.741214+00
55	195	7	550.68	t	2026-10-02 00:50:41.741214+00
56	222	5	218.79	t	2026-10-02 00:50:41.741214+00
57	222	7	329.00	t	2026-10-02 00:50:41.741214+00
58	229	5	527.78	t	2026-10-02 00:50:41.741214+00
59	246	11	663.71	t	2026-10-02 00:50:41.741214+00
60	260	2	405.60	t	2026-10-02 00:50:41.741214+00
61	262	2	739.60	t	2026-10-02 00:50:41.741214+00
62	266	2	553.10	t	2026-10-02 00:50:41.741214+00
63	274	2	626.55	t	2026-10-02 00:50:41.741214+00
64	278	1	221.24	t	2026-10-02 00:50:41.741214+00
65	289	3	663.34	t	2026-10-02 00:50:41.741214+00
66	291	3	663.34	t	2026-10-02 00:50:41.741214+00
67	293	3	700.00	t	2026-10-02 00:50:41.741214+00
68	298	11	1253.69	t	2026-10-02 00:50:41.741214+00
69	298	12	1185.64	t	2026-10-02 00:50:41.741214+00
70	303	11	1253.69	t	2026-10-02 00:50:41.741214+00
71	309	4	296.46	t	2026-10-02 00:50:41.741214+00
72	309	11	225.41	t	2026-10-02 00:50:41.741214+00
73	310	4	296.46	t	2026-10-02 00:50:41.741214+00
74	345	3	700.00	t	2026-10-02 00:50:41.741214+00
75	350	13	149.90	t	2026-10-02 00:50:41.741214+00
76	361	5	86.96	t	2026-10-02 00:50:41.741214+00
77	362	5	126.31	t	2026-10-02 00:50:41.741214+00
78	364	5	85.00	t	2026-10-02 00:50:41.741214+00
79	378	11	294.99	t	2026-10-02 00:50:41.741214+00
80	379	11	294.99	t	2026-10-02 00:50:41.741214+00
81	399	3	1639.75	t	2026-10-02 00:50:41.741214+00
82	401	2	589.97	t	2026-10-02 00:50:41.741214+00
83	441	4	862.83	t	2026-10-02 00:50:41.741214+00
84	498	7	73.00	t	2026-10-02 00:50:41.741214+00
85	506	6	387.17	t	2026-10-02 00:50:41.741214+00
86	528	7	81.05	t	2026-10-02 00:50:41.741214+00
87	538	2	479.65	t	2026-10-02 00:50:41.741214+00
88	543	2	346.90	t	2026-10-02 00:50:41.741214+00
89	548	3	405.60	t	2026-10-02 00:50:41.741214+00
90	566	3	264.32	t	2026-10-02 00:50:41.741214+00
91	569	3	811.20	t	2026-10-02 00:50:41.741214+00
92	570	3	811.00	t	2026-10-02 00:50:41.741214+00
93	571	3	535.56	t	2026-10-02 00:50:41.741214+00
94	571	12	513.27	t	2026-10-02 00:50:41.741214+00
95	592	4	455.75	t	2026-10-02 00:50:41.741214+00
96	615	1	147.49	t	2026-10-02 00:50:41.741214+00
97	625	7	305.31	t	2026-10-02 00:50:41.741214+00
98	626	3	1639.75	t	2026-10-02 00:50:41.741214+00
99	641	2	303.29	t	2026-10-02 00:50:41.741214+00
100	652	4	862.83	t	2026-10-02 00:50:41.741214+00
101	654	11	833.33	t	2026-10-02 00:50:41.741214+00
102	667	11	1253.69	t	2026-10-02 00:50:41.741214+00
103	667	12	1185.64	t	2026-10-02 00:50:41.741214+00
104	680	3	1639.75	t	2026-10-02 00:50:41.741214+00
105	688	4	567.00	t	2026-10-02 00:50:41.741214+00
106	695	4	455.75	t	2026-10-02 00:50:41.741214+00
107	740	3	896.62	t	2026-10-02 00:50:41.741214+00
108	771	4	176.99	t	2026-10-02 00:50:41.741214+00
109	779	3	353.98	t	2026-10-02 00:50:41.741214+00
110	787	3	24.00	t	2026-10-02 00:50:41.741214+00
111	794	5	701.09	t	2026-10-02 00:50:41.741214+00
112	805	1	149.49	t	2026-10-02 00:50:41.741214+00
113	811	4	1000.00	t	2026-10-02 00:50:41.741214+00
114	812	4	1000.00	t	2026-10-02 00:50:41.741214+00
115	813	4	1000.00	t	2026-10-02 00:50:41.741214+00
116	814	4	1000.00	t	2026-10-02 00:50:41.741214+00
117	817	5	394.34	t	2026-10-02 00:50:41.741214+00
118	837	10	147.30	t	2026-10-02 00:50:41.741214+00
119	839	1	442.48	t	2026-10-02 00:50:41.741214+00
120	840	4	566.37	t	2026-10-02 00:50:41.741214+00
121	841	4	1100.00	t	2026-10-02 00:50:41.741214+00
122	844	10	142.10	t	2026-10-02 00:50:41.741214+00
123	851	10	336.00	t	2026-10-02 00:50:41.741214+00
124	854	10	479.00	t	2026-10-02 00:50:41.741214+00
125	855	10	203.90	t	2026-10-02 00:50:41.741214+00
126	857	10	163.70	t	2026-10-02 00:50:41.741214+00
127	878	5	602.00	t	2026-10-02 00:50:41.741214+00
128	880	5	849.60	t	2026-10-02 00:50:41.741214+00
129	884	4	415.93	t	2026-10-02 00:50:41.741214+00
130	888	10	360.15	t	2026-10-02 00:50:41.741214+00
131	904	15	142.00	t	2026-10-02 00:50:41.741214+00
132	941	7	2756.35	t	2026-10-02 00:50:41.741214+00
133	942	7	2611.00	t	2026-10-02 00:50:41.741214+00
134	948	1	110.62	t	2026-10-02 00:50:41.741214+00
135	949	1	110.62	t	2026-10-02 00:50:41.741214+00
136	951	1	368.73	t	2026-10-02 00:50:41.741214+00
137	952	7	246.16	t	2026-10-02 00:50:41.741214+00
138	958	9	442.47	t	2026-10-02 00:50:41.741214+00
139	961	1	115.05	t	2026-10-02 00:50:41.741214+00
140	967	15	331.66	t	2026-10-02 00:50:41.741214+00
141	970	15	405.60	t	2026-10-02 00:50:41.741214+00
142	972	15	143.89	t	2026-10-02 00:50:41.741214+00
143	974	15	143.89	t	2026-10-02 00:50:41.741214+00
144	975	10	198.00	t	2026-10-02 00:50:41.741214+00
145	1005	2	412.33	t	2026-10-02 00:50:41.741214+00
146	1009	10	707.94	t	2026-10-02 00:50:41.741214+00
147	1010	10	707.94	t	2026-10-02 00:50:41.741214+00
148	1041	5	1051.00	t	2026-10-02 00:50:41.741214+00
149	1044	7	680.97	t	2026-10-02 00:50:41.741214+00
150	1045	11	961.06	t	2026-10-02 00:50:41.741214+00
151	1060	4	1269.91	t	2026-10-02 00:50:41.741214+00
152	1061	7	453.98	t	2026-10-02 00:50:41.741214+00
153	1062	7	151.33	t	2026-10-02 00:50:41.741214+00
154	1063	7	151.33	t	2026-10-02 00:50:41.741214+00
155	1068	7	702.79	t	2026-10-02 00:50:41.741214+00
156	1069	7	639.58	t	2026-10-02 00:50:41.741214+00
157	1073	11	1474.90	t	2026-10-02 00:50:41.741214+00
158	1074	15	212.39	t	2026-10-02 00:50:41.741214+00
159	1079	7	264.16	t	2026-10-02 00:50:41.741214+00
160	1080	7	264.16	t	2026-10-02 00:50:41.741214+00
161	1093	3	1639.75	t	2026-10-02 00:50:41.741214+00
162	1095	3	1639.75	t	2026-10-02 00:50:41.741214+00
163	1099	2	221.00	t	2026-10-02 00:50:41.741214+00
164	1102	1	1327.00	t	2026-10-02 00:50:41.741214+00
165	1116	1	221.24	t	2026-10-02 00:50:41.741214+00
166	1126	10	186.50	t	2026-10-02 00:50:41.741214+00
167	1127	10	213.00	t	2026-10-02 00:50:41.741214+00
168	1128	11	737.46	t	2026-10-02 00:50:41.741214+00
169	1136	15	204.22	t	2026-10-02 00:50:41.741214+00
170	1137	15	247.78	t	2026-10-02 00:50:41.741214+00
171	1138	15	247.78	t	2026-10-02 00:50:41.741214+00
172	1144	16	1182.30	t	2026-10-02 00:50:41.741214+00
173	1145	16	1182.30	t	2026-10-02 00:50:41.741214+00
174	1148	5	456.55	t	2026-10-02 00:50:41.741214+00
175	1152	5	442.50	t	2026-10-02 00:50:41.741214+00
176	1154	5	672.00	t	2026-10-02 00:50:41.741214+00
177	1155	10	29.45	t	2026-10-02 00:50:41.741214+00
178	1164	7	94.14	t	2026-10-02 00:50:41.741214+00
179	1166	10	98.00	t	2026-10-02 00:50:41.741214+00
180	1192	5	726.00	t	2026-10-02 00:50:41.741214+00
181	1194	1	184.37	t	2026-10-02 00:50:41.741214+00
182	1196	10	318.58	t	2026-10-02 00:50:41.741214+00
183	1222	7	305.00	t	2026-10-02 00:50:41.741214+00
184	1224	17	945.00	t	2026-10-02 00:50:41.741214+00
185	1225	17	1190.00	t	2026-10-02 00:50:41.741214+00
186	1226	17	840.00	t	2026-10-02 00:50:41.741214+00
187	1227	17	1080.00	t	2026-10-02 00:50:41.741214+00
188	1228	17	1075.00	t	2026-10-02 00:50:41.741214+00
189	1229	17	1075.00	t	2026-10-02 00:50:41.741214+00
190	1230	17	1075.00	t	2026-10-02 00:50:41.741214+00
191	1231	10	530.83	t	2026-10-02 00:50:41.741214+00
192	1232	10	584.07	t	2026-10-02 00:50:41.741214+00
193	1233	5	600.70	t	2026-10-02 00:50:41.741214+00
194	1243	1	106.20	t	2026-10-02 00:50:41.741214+00
195	1253	12	380.53	t	2026-10-02 00:50:41.741214+00
196	1255	12	986.72	t	2026-10-02 00:50:41.741214+00
197	1257	12	318.58	t	2026-10-02 00:50:41.741214+00
198	1258	12	512.00	t	2026-10-02 00:50:41.741214+00
199	1259	12	736.28	t	2026-10-02 00:50:41.741214+00
200	1260	12	527.43	t	2026-10-02 00:50:41.741214+00
201	1262	5	1301.12	t	2026-10-02 00:50:41.741214+00
202	1263	5	340.00	t	2026-10-02 00:50:41.741214+00
203	1264	5	267.00	t	2026-10-02 00:50:41.741214+00
204	1265	5	460.00	t	2026-10-02 00:50:41.741214+00
205	1271	5	648.00	t	2026-10-02 00:50:41.741214+00
206	1272	5	1191.00	t	2026-10-02 00:50:41.741214+00
207	1273	5	376.87	t	2026-10-02 00:50:41.741214+00
208	1274	5	343.71	t	2026-10-02 00:50:41.741214+00
209	1275	12	1167.25	t	2026-10-02 00:50:41.741214+00
210	1276	12	1167.25	t	2026-10-02 00:50:41.741214+00
211	1277	12	1167.25	t	2026-10-02 00:50:41.741214+00
212	1279	12	1167.25	t	2026-10-02 00:50:41.741214+00
213	1314	16	379.00	t	2026-10-02 00:50:41.741214+00
214	1328	1	368.73	t	2026-10-02 00:50:41.741214+00
215	1329	1	826.00	t	2026-10-02 00:50:41.741214+00
216	1330	1	73.75	t	2026-10-02 00:50:41.741214+00
217	1331	2	884.96	t	2026-10-02 00:50:41.741214+00
218	1332	2	966.08	t	2026-10-02 00:50:41.741214+00
219	1333	2	789.00	t	2026-10-02 00:50:41.741214+00
220	1334	3	92.19	t	2026-10-02 00:50:41.741214+00
221	1335	3	1639.75	t	2026-10-02 00:50:41.741214+00
222	1336	3	1639.75	t	2026-10-02 00:50:41.741214+00
223	1337	3	1639.75	t	2026-10-02 00:50:41.741214+00
224	1338	3	110.30	t	2026-10-02 00:50:41.741214+00
225	1339	3	921.83	t	2026-10-02 00:50:41.741214+00
226	1340	3	663.00	t	2026-10-02 00:50:41.741214+00
227	1341	3	542.60	t	2026-10-02 00:50:41.741214+00
228	1342	4	398.23	t	2026-10-02 00:50:41.741214+00
229	1343	4	650.44	t	2026-10-02 00:50:41.741214+00
230	1344	4	525.00	t	2026-10-02 00:50:41.741214+00
231	1345	4	80.00	t	2026-10-02 00:50:41.741214+00
232	1346	4	80.00	t	2026-10-02 00:50:41.741214+00
233	1347	4	862.83	t	2026-10-02 00:50:41.741214+00
234	1348	4	36.87	t	2026-10-02 00:50:41.741214+00
235	1349	4	82.80	t	2026-10-02 00:50:41.741214+00
236	1350	4	84.06	t	2026-10-02 00:50:41.741214+00
237	1351	4	332.00	t	2026-10-02 00:50:41.741214+00
238	1352	4	331.86	t	2026-10-02 00:50:41.741214+00
239	1353	4	71.00	t	2026-10-02 00:50:41.741214+00
240	1354	4	324.48	t	2026-10-02 00:50:41.741214+00
241	1355	4	433.63	t	2026-10-02 00:50:41.741214+00
242	1356	4	475.00	t	2026-10-02 00:50:41.741214+00
243	1357	5	922.00	t	2026-10-02 00:50:41.741214+00
244	1358	5	590.13	t	2026-10-02 00:50:41.741214+00
245	1359	5	767.91	t	2026-10-02 00:50:41.741214+00
246	1360	5	856.95	t	2026-10-02 00:50:41.741214+00
247	1361	5	328.31	t	2026-10-02 00:50:41.741214+00
248	1362	5	328.31	t	2026-10-02 00:50:41.741214+00
249	1363	5	643.34	t	2026-10-02 00:50:41.741214+00
250	1364	5	1881.33	t	2026-10-02 00:50:41.741214+00
251	1365	5	375.31	t	2026-10-02 00:50:41.741214+00
252	1366	5	16.65	t	2026-10-02 00:50:41.741214+00
253	1367	5	441.89	t	2026-10-02 00:50:41.741214+00
254	1368	5	374.01	t	2026-10-02 00:50:41.741214+00
255	1369	5	365.00	t	2026-10-02 00:50:41.741214+00
256	1370	6	387.17	t	2026-10-02 00:50:41.741214+00
257	1371	6	147.49	t	2026-10-02 00:50:41.741214+00
258	1372	6	553.10	t	2026-10-02 00:50:41.741214+00
259	1373	6	553.10	t	2026-10-02 00:50:41.741214+00
260	1374	6	716.00	t	2026-10-02 00:50:41.741214+00
261	1375	6	184.37	t	2026-10-02 00:50:41.741214+00
262	1376	6	119.83	t	2026-10-02 00:50:41.741214+00
263	1377	7	151.33	t	2026-10-02 00:50:41.741214+00
264	1378	7	151.33	t	2026-10-02 00:50:41.741214+00
265	1379	8	235.12	t	2026-10-02 00:50:41.741214+00
266	1380	9	442.47	t	2026-10-02 00:50:41.741214+00
267	1381	10	186.00	t	2026-10-02 00:50:41.741214+00
268	1382	10	131.00	t	2026-10-02 00:50:41.741214+00
269	1383	10	160.00	t	2026-10-02 00:50:41.741214+00
270	1384	10	144.26	t	2026-10-02 00:50:41.741214+00
271	1385	10	80.00	t	2026-10-02 00:50:41.741214+00
272	1386	10	166.00	t	2026-10-02 00:50:41.741214+00
273	1387	10	489.00	t	2026-10-02 00:50:41.741214+00
274	1388	10	129.20	t	2026-10-02 00:50:41.741214+00
275	1389	10	707.94	t	2026-10-02 00:50:41.741214+00
276	1390	10	138.33	t	2026-10-02 00:50:41.741214+00
277	1391	10	161.00	t	2026-10-02 00:50:41.741214+00
278	1392	11	917.00	t	2026-10-02 00:50:41.741214+00
279	1393	11	700.59	t	2026-10-02 00:50:41.741214+00
280	1394	12	1167.25	t	2026-10-02 00:50:41.741214+00
281	1395	12	1083.18	t	2026-10-02 00:50:41.741214+00
282	1396	12	1083.18	t	2026-10-02 00:50:41.741214+00
283	1397	12	1083.18	t	2026-10-02 00:50:41.741214+00
284	1398	12	1083.18	t	2026-10-02 00:50:41.741214+00
285	1399	12	905.00	t	2026-10-02 00:50:41.741214+00
286	1400	12	512.38	t	2026-10-02 00:50:41.741214+00
287	1401	12	876.10	t	2026-10-02 00:50:41.741214+00
288	1402	12	0.00	t	2026-10-02 00:50:41.741214+00
289	1403	12	0.00	t	2026-10-02 00:50:41.741214+00
290	1404	12	0.00	t	2026-10-02 00:50:41.741214+00
291	1405	12	0.00	t	2026-10-02 00:50:41.741214+00
292	1406	12	0.00	t	2026-10-02 00:50:41.741214+00
293	1407	12	2663.71	t	2026-10-02 00:50:41.741214+00
294	1408	12	70.09	t	2026-10-02 00:50:41.741214+00
295	1409	13	601.77	t	2026-10-02 00:50:41.741214+00
296	1410	13	601.77	t	2026-10-02 00:50:41.741214+00
297	1411	13	461.10	t	2026-10-02 00:50:41.741214+00
298	1412	13	55.00	t	2026-10-02 00:50:41.741214+00
299	1413	13	1530.00	t	2026-10-02 00:50:41.741214+00
300	1414	14	219.00	t	2026-10-02 00:50:41.741214+00
301	1415	14	8402.65	t	2026-10-02 00:50:41.741214+00
302	1416	15	318.58	t	2026-10-02 00:50:41.741214+00
303	1417	15	141.59	t	2026-10-02 00:50:41.741214+00
304	1418	16	124.00	t	2026-10-02 00:50:41.741214+00
305	1419	18	1192.15	t	2026-10-02 00:50:41.741214+00
\.


--
-- Data for Name: proveedor; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.proveedor (id_proveedor, nombre, contacto, telefono, correo, direccion, estado) FROM stdin;
1	Dinant	\N	\N	\N	\N	t
2	Florida	\N	\N	\N	\N	t
3	Dimarpa	\N	\N	\N	\N	t
4	Aguilar	\N	\N	\N	\N	t
5	Dos Pinos	\N	\N	\N	\N	t
6	Bimbo	\N	\N	\N	\N	t
7	Salvador Ramirez	\N	\N	\N	\N	t
8	Coarsa	\N	\N	\N	\N	t
9	Diana	\N	\N	\N	\N	t
10	Pozuelo	\N	\N	\N	\N	t
11	Coca Cola	\N	\N	\N	\N	t
12	Emsar Global	\N	\N	\N	\N	t
13	Disal	\N	\N	\N	\N	t
14	PriceSmart	\N	\N	\N	\N	t
15	Jack	\N	\N	\N	\N	t
16	Dipo	\N	\N	\N	\N	t
17	Distribuidora J y J	\N	\N	\N	\N	t
18	Preparatos	\N	\N	\N	\N	t
\.


--
-- Data for Name: recuperacion_contrasena; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.recuperacion_contrasena (id_recuperacion, id_usuario, token_hash, fecha_solicitud, fecha_expiracion, fecha_uso, estado) FROM stdin;
2	4	994cd81dc784b31e0e98878bb15c28629bf12c379a6d4e8adef2407a9e00fe5a	2026-10-05 17:42:51.934669+00	2026-10-05 17:57:51+00	\N	f
3	4	241426764988fe6442b930b398e762e24480a50374e1fb866046130fc48c65d9	2026-10-05 17:52:38.881901+00	2026-10-05 18:07:38+00	\N	f
1	1	dfe92638224419b0dd6a4c7649e30f200d310ccb0f58ae35df22c0bbc82ad3f7	2026-10-05 17:39:29.217242+00	2026-10-05 17:54:29+00	\N	f
5	1	b2d8d9b4f8fad592e2300c7cf4d434a69d1afb0046ddb2047fdc498d4585f00e	2026-10-05 17:53:27.430828+00	2026-10-05 18:08:27+00	\N	f
4	4	526e761aca60726094d1a796353a5b8cb53cd96db56bd50524b139e584afa52e	2026-10-05 17:53:07.746758+00	2026-10-05 18:08:07+00	\N	f
6	4	a166a9ffaa2607abe033c07935619ff5c2a761a280e0f084246364ccd29d90ab	2026-10-05 19:14:57.009907+00	2026-10-05 19:29:56+00	2026-10-05 19:17:05.935781+00	f
7	1	ba18482983493f8bbbe6479e0936567064d158cec7f00f5de0a9ec47c7c16cfd	2026-10-05 21:58:13.083493+00	2026-10-05 22:13:12+00	\N	t
8	4	6feee1634903a8b88fcd679d65de29880993deddc075629e0590688bd07602b4	2026-10-05 21:58:27.340876+00	2026-10-05 22:13:27+00	\N	f
9	4	d2abe17e327b86c93953ae45c1dc41f59cca1a6b250e6df042182c24298208ac	2026-10-05 22:00:12.643062+00	2026-10-05 22:15:11+00	\N	f
10	4	a289438a25fc0c918c7db31c043aced73f593565ba4792e0f011c99014559836	2026-10-05 22:02:53.903742+00	2026-10-05 22:17:53+00	2026-10-05 22:03:36.852139+00	f
11	4	63f6f42a3784e1a35e19897f2d5f6a34d21c6e28280962d3aaf51e719bd9e5b0	2026-10-05 22:09:29.79931+00	2026-10-05 22:24:29+00	2026-10-05 22:10:16.020346+00	f
12	4	a442866f6ab478c8e1464bdab66a5b155bb3281e2a2bc3031123090ff2e69e84	2026-10-05 22:16:06.813217+00	2026-10-05 22:31:06+00	2026-10-05 22:16:40.077883+00	f
\.


--
-- Data for Name: relacion_abastecimiento; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.relacion_abastecimiento (id_relacion, id_tienda_origen, id_tienda_destino, id_maquina_destino, estado, observaciones, fecha_registro) FROM stdin;
1	2	3	\N	t	UP2 abastece a UltraLag y sus maquinas	2026-10-06 23:55:57.195365+00
\.


--
-- Data for Name: respaldo; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.respaldo (id_respaldo, id_usuario, tipo, fecha_inicio, fecha_fin, estado, cobertura, referencia, observaciones, fecha_registro) FROM stdin;
1	4	MANUAL	2026-10-07 06:04:37.226059+00	2026-10-07 06:04:37.226059+00	EXITOSO	Base de datos y información crítica	RESPALDO-PRUEBA-HU06	Registro de prueba para HU-06	2026-10-07 06:04:37.226059+00
\.


--
-- Data for Name: rol; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.rol (id_rol, nombre, descripcion, estado) FROM stdin;
1	Administrador	Acceso completo al sistema	t
2	Gerente General	Consulta general de la operación	t
3	Encargado	Gestión operativa autorizada	t
4	Dependiente	Trabajo operativo en la tienda	t
\.


--
-- Data for Name: tienda; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tienda (id_tienda, nombre, ubicacion, estado, tipo) FROM stdin;
1	Ultrapark 1	Ultrapark 1	t	TIENDA
2	Ultrapark 2	Ultrapark 2	t	TIENDA
3	UltraLag	UltraLag	t	TIENDA
\.


--
-- Data for Name: usuario; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.usuario (id_usuario, id_rol, nombre, apellido, correo, password_hash, estado, ultimo_acceso, intentos_fallidos, bloqueado_hasta) FROM stdin;
5	4	Raúl	Pérez	raul@vending.com	$2y$10$dCzMoFRBXzCMckFHQ0kSK.7qL7enYVQyxLRwZYi1oYjQ31kGRwb0K	t	\N	0	\N
1	1	Administrador	Prueba	admin@vending.com	$2y$10$TKLa.C2vxUMmWo6KKxd5A.DC7W65yg2wFBEdf.YJ/PrbOjN76YoQm	t	2026-10-06 06:52:47.369828+00	0	\N
3	4	Pedro	Gomez	pedro@vending.com	$2y$10$LWyPJVm2pMRj7p/YHC/Tu.7s3HTWta76O3WYuNur6lIR8pkbrBqi.	t	2026-10-06 22:24:31.690872+00	0	\N
4	1	Isaac	Aleman	isaacalemanb@gmail.com	$2y$10$j8WbKXuI7.5Id0aMCUWGEONlrRjEj6cniu3ekBirkgAEruGrVvS6C	t	2026-10-07 20:39:24.322713+00	0	\N
2	4	Juan Carlos	Perez	juan@vending.com	$2y$10$XFRXY3gGUtgX3WX4R2c/uO.MGjIZXeT7kE0frnSvN9G/AaUrYMLfW	t	2026-10-03 08:10:45.805882+00	0	\N
\.


--
-- Data for Name: usuario_tienda; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.usuario_tienda (id_usuario_tienda, id_usuario, id_tienda, estado) FROM stdin;
1	3	2	t
2	4	1	t
3	5	1	t
\.


--
-- Data for Name: schema_migrations; Type: TABLE DATA; Schema: realtime; Owner: supabase_admin
--

COPY realtime.schema_migrations (version, inserted_at) FROM stdin;
20211116024918	2026-09-25 00:09:59
20211116045059	2026-09-25 00:09:59
20211116050929	2026-09-25 00:09:59
20211116051442	2026-09-25 00:09:59
20211116212300	2026-09-25 00:09:59
20211116213355	2026-09-25 00:09:59
20211116213934	2026-09-25 00:09:59
20211116214523	2026-09-25 00:09:59
20211122062447	2026-09-25 00:09:59
20211124070109	2026-09-25 00:09:59
20211202204204	2026-09-25 00:09:59
20211202204605	2026-09-25 00:09:59
20211210212804	2026-09-25 00:09:59
20211228014915	2026-09-25 00:09:59
20220107221237	2026-09-25 00:09:59
20220228202821	2026-09-25 00:09:59
20220312004840	2026-09-25 00:09:59
20220603231003	2026-09-25 00:09:59
20220603232444	2026-09-25 00:09:59
20220615214548	2026-09-25 00:09:59
20220712093339	2026-09-25 00:09:59
20220908172859	2026-09-25 00:09:59
20220916233421	2026-09-25 00:09:59
20230119133233	2026-09-25 00:09:59
20230128025114	2026-09-25 00:09:59
20230128025212	2026-09-25 00:09:59
20230227211149	2026-09-25 00:09:59
20230228184745	2026-09-25 00:09:59
20230308225145	2026-09-25 00:09:59
20230328144023	2026-09-25 00:09:59
20231018144023	2026-09-25 00:09:59
20231204144023	2026-09-25 00:09:59
20231204144024	2026-09-25 00:09:59
20231204144025	2026-09-25 00:09:59
20240108234812	2026-09-25 00:09:59
20240109165339	2026-09-25 00:09:59
20240227174441	2026-09-25 00:09:59
20240311171622	2026-09-25 00:09:59
20240321100241	2026-09-25 00:09:59
20240401105812	2026-09-25 00:09:59
20240418121054	2026-09-25 00:09:59
20240523004032	2026-09-25 00:09:59
20240618124746	2026-09-25 00:09:59
20240801235015	2026-09-25 00:09:59
20240805133720	2026-09-25 00:09:59
20240827160934	2026-09-25 00:09:59
20240919163303	2026-09-25 00:09:59
20240919163305	2026-09-25 00:09:59
20241019105805	2026-09-25 00:09:59
20241030150047	2026-09-25 00:09:59
20241108114728	2026-09-25 00:09:59
20241121104152	2026-09-25 00:09:59
20241130184212	2026-09-25 00:09:59
20241220035512	2026-09-25 00:09:59
20241220123912	2026-09-25 00:09:59
20241224161212	2026-09-25 00:09:59
20250107150512	2026-09-25 00:09:59
20250110162412	2026-09-25 00:09:59
20250123174212	2026-09-25 00:09:59
20250128220012	2026-09-25 00:09:59
20250506224012	2026-09-25 00:09:59
20250523164012	2026-09-25 00:09:59
20250714121412	2026-09-25 00:09:59
20250905041441	2026-09-25 00:09:59
20251103001201	2026-09-25 00:09:59
20251120212548	2026-09-25 00:09:59
20251120215549	2026-09-25 00:09:59
20260218120000	2026-09-25 00:09:59
20260326120000	2026-09-25 00:09:59
20260514120000	2026-09-25 00:09:59
20260527120000	2026-09-25 00:09:59
20260528120000	2026-09-25 00:09:59
20260603120000	2026-09-25 00:09:59
20260605120000	2026-09-25 00:09:59
20260606110000	2026-09-25 00:09:59
20260616120000	2026-09-25 00:09:59
20260624120000	2026-09-25 00:09:59
20260626120000	2026-09-25 00:09:59
20260706120000	2026-09-25 00:09:59
20260707120000	2026-09-25 00:09:59
20260709120000	2026-09-25 00:09:59
20260714120000	2026-09-25 00:09:59
20260827120000	2026-09-25 00:09:59
20260914120000	2026-09-25 00:09:59
20260916120000	2026-09-25 00:09:59
20260922120000	2026-09-25 00:09:59
20260925120000	2026-10-01 03:41:09
20260928120000	2026-10-06 05:30:21
\.


--
-- Data for Name: subscription; Type: TABLE DATA; Schema: realtime; Owner: supabase_realtime_admin
--

COPY realtime.subscription (id, subscription_id, entity, filters, claims, created_at, action_filter, selected_columns) FROM stdin;
\.


--
-- Data for Name: buckets; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.buckets (id, name, owner, created_at, updated_at, public, avif_autodetection, file_size_limit, allowed_mime_types, owner_id, type, versioning_status, lifecycle_configuration, lifecycle_configuration_generation) FROM stdin;
\.


--
-- Data for Name: buckets_analytics; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.buckets_analytics (name, type, format, created_at, updated_at, id, deleted_at) FROM stdin;
\.


--
-- Data for Name: buckets_vectors; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.buckets_vectors (id, type, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.migrations (id, name, hash, executed_at) FROM stdin;
0	create-migrations-table	e18db593bcde2aca2a408c4d1100f6abba2195df	2026-09-25 00:09:59.669432
1	initialmigration	6ab16121fbaa08bbd11b712d05f358f9b555d777	2026-09-25 00:09:59.706576
2	storage-schema	f6a1fa2c93cbcd16d4e487b362e45fca157a8dbd	2026-09-25 00:09:59.785988
3	pathtoken-column	2cb1b0004b817b29d5b0a971af16bafeede4b70d	2026-09-25 00:09:59.82333
4	add-migrations-rls	427c5b63fe1c5937495d9c635c263ee7a5905058	2026-09-25 00:09:59.848138
5	add-size-functions	79e081a1455b63666c1294a440f8ad4b1e6a7f84	2026-09-25 00:09:59.852091
6	change-column-name-in-get-size	ded78e2f1b5d7e616117897e6443a925965b30d2	2026-09-25 00:09:59.856458
7	add-rls-to-buckets	e7e7f86adbc51049f341dfe8d30256c1abca17aa	2026-09-25 00:09:59.860968
8	add-public-to-buckets	fd670db39ed65f9d08b01db09d6202503ca2bab3	2026-09-25 00:09:59.864982
9	fix-search-function	af597a1b590c70519b464a4ab3be54490712796b	2026-09-25 00:09:59.869456
10	search-files-search-function	b595f05e92f7e91211af1bbfe9c6a13bb3391e16	2026-09-25 00:09:59.873883
11	add-trigger-to-auto-update-updated_at-column	7425bdb14366d1739fa8a18c83100636d74dcaa2	2026-09-25 00:09:59.878155
12	add-automatic-avif-detection-flag	8e92e1266eb29518b6a4c5313ab8f29dd0d08df9	2026-09-25 00:09:59.88315
13	add-bucket-custom-limits	cce962054138135cd9a8c4bcd531598684b25e7d	2026-09-25 00:09:59.887215
14	use-bytes-for-max-size	941c41b346f9802b411f06f30e972ad4744dad27	2026-09-25 00:09:59.891494
15	add-can-insert-object-function	934146bc38ead475f4ef4b555c524ee5d66799e5	2026-09-25 00:09:59.914777
16	add-version	76debf38d3fd07dcfc747ca49096457d95b1221b	2026-09-25 00:09:59.919063
17	drop-owner-foreign-key	f1cbb288f1b7a4c1eb8c38504b80ae2a0153d101	2026-09-25 00:09:59.923471
18	add_owner_id_column_deprecate_owner	e7a511b379110b08e2f214be852c35414749fe66	2026-09-25 00:09:59.927704
19	alter-default-value-objects-id	02e5e22a78626187e00d173dc45f58fa66a4f043	2026-09-25 00:09:59.932948
20	list-objects-with-delimiter	cd694ae708e51ba82bf012bba00caf4f3b6393b7	2026-09-25 00:09:59.937157
21	s3-multipart-uploads	8c804d4a566c40cd1e4cc5b3725a664a9303657f	2026-09-25 00:09:59.942827
22	s3-multipart-uploads-big-ints	9737dc258d2397953c9953d9b86920b8be0cdb73	2026-09-25 00:09:59.958878
23	optimize-search-function	9d7e604cddc4b56a5422dc68c9313f4a1b6f132c	2026-09-25 00:09:59.97142
24	operation-function	8312e37c2bf9e76bbe841aa5fda889206d2bf8aa	2026-09-25 00:09:59.976216
25	custom-metadata	d974c6057c3db1c1f847afa0e291e6165693b990	2026-09-25 00:09:59.983907
26	objects-prefixes	215cabcb7f78121892a5a2037a09fedf9a1ae322	2026-09-25 00:09:59.988322
27	search-v2	859ba38092ac96eb3964d83bf53ccc0b141663a6	2026-09-25 00:09:59.993067
28	object-bucket-name-sorting	c73a2b5b5d4041e39705814fd3a1b95502d38ce4	2026-09-25 00:09:59.996826
29	create-prefixes	ad2c1207f76703d11a9f9007f821620017a66c21	2026-09-25 00:10:00.001097
30	update-object-levels	2be814ff05c8252fdfdc7cfb4b7f5c7e17f0bed6	2026-09-25 00:10:00.005183
31	objects-level-index	b40367c14c3440ec75f19bbce2d71e914ddd3da0	2026-09-25 00:10:00.009765
32	backward-compatible-index-on-objects	e0c37182b0f7aee3efd823298fb3c76f1042c0f7	2026-09-25 00:10:00.01475
33	backward-compatible-index-on-prefixes	b480e99ed951e0900f033ec4eb34b5bdcb4e3d49	2026-09-25 00:10:00.018463
34	optimize-search-function-v1	ca80a3dc7bfef894df17108785ce29a7fc8ee456	2026-09-25 00:10:00.022049
35	add-insert-trigger-prefixes	458fe0ffd07ec53f5e3ce9df51bfdf4861929ccc	2026-09-25 00:10:00.025943
36	optimise-existing-functions	6ae5fca6af5c55abe95369cd4f93985d1814ca8f	2026-09-25 00:10:00.029719
37	add-bucket-name-length-trigger	3944135b4e3e8b22d6d4cbb568fe3b0b51df15c1	2026-09-25 00:10:00.033504
38	iceberg-catalog-flag-on-buckets	02716b81ceec9705aed84aa1501657095b32e5c5	2026-09-25 00:10:00.03909
39	add-search-v2-sort-support	6706c5f2928846abee18461279799ad12b279b78	2026-09-25 00:10:00.050095
40	fix-prefix-race-conditions-optimized	7ad69982ae2d372b21f48fc4829ae9752c518f6b	2026-09-25 00:10:00.055761
41	add-object-level-update-trigger	07fcf1a22165849b7a029deed059ffcde08d1ae0	2026-09-25 00:10:00.067069
42	rollback-prefix-triggers	771479077764adc09e2ea2043eb627503c034cd4	2026-09-25 00:10:00.070568
43	fix-object-level	84b35d6caca9d937478ad8a797491f38b8c2979f	2026-09-25 00:10:00.075505
44	vector-bucket-type	99c20c0ffd52bb1ff1f32fb992f3b351e3ef8fb3	2026-09-25 00:10:00.079953
45	vector-buckets	049e27196d77a7cb76497a85afae669d8b230953	2026-09-25 00:10:00.084202
46	buckets-objects-grants	fedeb96d60fefd8e02ab3ded9fbde05632f84aed	2026-09-25 00:10:00.104222
47	iceberg-table-metadata	649df56855c24d8b36dd4cc1aeb8251aa9ad42c2	2026-09-25 00:10:00.110376
48	iceberg-catalog-ids	e0e8b460c609b9999ccd0df9ad14294613eed939	2026-09-25 00:10:00.113799
49	buckets-objects-grants-postgres	072b1195d0d5a2f888af6b2302a1938dd94b8b3d	2026-09-25 00:10:00.134014
50	search-v2-optimised	6323ac4f850aa14e7387eb32102869578b5bd478	2026-09-25 00:10:00.139844
51	index-backward-compatible-search	2ee395d433f76e38bcd3856debaf6e0e5b674011	2026-09-25 00:10:00.158432
52	drop-not-used-indexes-and-functions	5cc44c8696749ac11dd0dc37f2a3802075f3a171	2026-09-25 00:10:00.160048
53	drop-index-lower-name	d0cb18777d9e2a98ebe0bc5cc7a42e57ebe41854	2026-09-25 00:10:00.171887
54	drop-index-object-level	6289e048b1472da17c31a7eba1ded625a6457e67	2026-09-25 00:10:00.174908
55	prevent-direct-deletes	262a4798d5e0f2e7c8970232e03ce8be695d5819	2026-09-25 00:10:00.176818
56	fix-optimized-search-function	b823ed1e418101032fa01374edc9a436e54e3ed4	2026-09-25 00:10:00.184046
57	s3-multipart-uploads-metadata	f127886e00d1b374fadbc7c6b31e09336aad5287	2026-09-25 00:10:00.190094
58	operation-ergonomics	00ca5d483b3fe0d522133d9002ccc5df98365120	2026-09-25 00:10:00.194132
59	drop-unused-functions	38456f13e39691c2bbb4b5151d0d1cdbabd4a8c4	2026-09-25 00:10:00.199323
60	optimize-existing-functions-again	db35e1c91a9201e59f4fef8d972c2f277d68b157	2026-09-25 00:10:00.203753
61	mark-filename-immutable	fe0096517ae9d60aaec1d110172ba9036dc66bb7	2026-09-25 00:10:00.208326
62	object-versioning-core	0b855f00ff3be0bfca91efee02a9858912491a9a	2026-09-25 00:10:00.212447
63	fix-search-name-relative-to-prefix	c7485e417624f795ce8bb2da21927f48e088904d	2026-09-25 00:10:00.223887
64	fix-search-by-timestamp-sqli	0af424ecd388a39bb1645184b222185a12149675	2026-09-25 00:10:00.230987
65	objects-key-version-index	da319c4b89ba800ce795d1b699f3a70675138058	2026-09-25 00:10:00.243926
66	objects-current-version-index	191466c93aa2c46a00e36505577c5fcab8d7cb4b	2026-09-25 00:10:00.252795
67	objects-null-version-index	15bfe8c35b66642b6c78ba60060fa8793bd2207a	2026-09-25 00:10:00.261842
68	bucket-lifecycle-configuration	3c08f6f889922f399519722a932b51007c11bebc	2026-09-25 00:10:00.263746
69	validate-bucket-lifecycle-constraints	4febacaaaa0e61e2b783bef081fe03a287e65eb3	2026-09-25 00:10:00.274623
70	list-objects-with-versions	5c17c3777616cd8d7b18b82835525fa3205af57b	2026-09-25 00:10:00.279948
71	objects-delete-marker-index	6d14858e66c66f8d6accf8a2630aefd1527fddba	2026-09-25 00:10:00.309061
72	drop-bucketid-objname-index	302beb09e1b469d7d4db19566f2389d280b64aa3	2026-09-25 00:10:00.316783
\.


--
-- Data for Name: objects; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.objects (id, bucket_id, name, owner, created_at, updated_at, last_accessed_at, metadata, version, owner_id, user_metadata, archived_at, is_delete_marker, is_versioned) FROM stdin;
\.


--
-- Data for Name: s3_multipart_uploads; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.s3_multipart_uploads (id, in_progress_size, upload_signature, bucket_id, key, version, owner_id, created_at, user_metadata, metadata) FROM stdin;
\.


--
-- Data for Name: s3_multipart_uploads_parts; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.s3_multipart_uploads_parts (id, upload_id, size, part_number, bucket_id, key, etag, owner_id, version, created_at) FROM stdin;
\.


--
-- Data for Name: vector_indexes; Type: TABLE DATA; Schema: storage; Owner: supabase_storage_admin
--

COPY storage.vector_indexes (id, name, bucket_id, data_type, dimension, distance_metric, metadata_configuration, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: secrets; Type: TABLE DATA; Schema: vault; Owner: supabase_admin
--

COPY vault.secrets (id, name, description, secret, key_id, nonce, created_at, updated_at) FROM stdin;
\.


--
-- Name: refresh_tokens_id_seq; Type: SEQUENCE SET; Schema: auth; Owner: supabase_auth_admin
--

SELECT pg_catalog.setval('auth.refresh_tokens_id_seq', 1, false);


--
-- Name: bitacora_auditoria_id_evento_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bitacora_auditoria_id_evento_seq', 53, true);


--
-- Name: categoria_id_categoria_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.categoria_id_categoria_seq', 198, true);


--
-- Name: impuesto_id_impuesto_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.impuesto_id_impuesto_seq', 49, true);


--
-- Name: maquina_id_maquina_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.maquina_id_maquina_seq', 2, true);


--
-- Name: maquina_producto_id_maquina_producto_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.maquina_producto_id_maquina_producto_seq', 1, false);


--
-- Name: margen_ganancia_id_margen_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.margen_ganancia_id_margen_seq', 76, true);


--
-- Name: precio_tienda_id_precio_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.precio_tienda_id_precio_seq', 1, false);


--
-- Name: producto_id_producto_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.producto_id_producto_seq', 1419, true);


--
-- Name: producto_proveedor_id_producto_proveedor_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.producto_proveedor_id_producto_proveedor_seq', 305, true);


--
-- Name: proveedor_id_proveedor_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.proveedor_id_proveedor_seq', 324, true);


--
-- Name: recuperacion_contrasena_id_recuperacion_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.recuperacion_contrasena_id_recuperacion_seq', 12, true);


--
-- Name: relacion_abastecimiento_id_relacion_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.relacion_abastecimiento_id_relacion_seq', 1, true);


--
-- Name: respaldo_id_respaldo_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.respaldo_id_respaldo_seq', 1, true);


--
-- Name: rol_id_rol_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.rol_id_rol_seq', 5, true);


--
-- Name: tienda_id_tienda_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tienda_id_tienda_seq', 3, true);


--
-- Name: usuario_id_usuario_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.usuario_id_usuario_seq', 5, true);


--
-- Name: usuario_tienda_id_usuario_tienda_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.usuario_tienda_id_usuario_tienda_seq', 3, true);


--
-- Name: subscription_id_seq; Type: SEQUENCE SET; Schema: realtime; Owner: supabase_realtime_admin
--

SELECT pg_catalog.setval('realtime.subscription_id_seq', 1, false);


--
-- Name: mfa_amr_claims amr_id_pk; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_amr_claims
    ADD CONSTRAINT amr_id_pk PRIMARY KEY (id);


--
-- Name: audit_log_entries audit_log_entries_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.audit_log_entries
    ADD CONSTRAINT audit_log_entries_pkey PRIMARY KEY (id);


--
-- Name: custom_oauth_providers custom_oauth_providers_identifier_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.custom_oauth_providers
    ADD CONSTRAINT custom_oauth_providers_identifier_key UNIQUE (identifier);


--
-- Name: custom_oauth_providers custom_oauth_providers_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.custom_oauth_providers
    ADD CONSTRAINT custom_oauth_providers_pkey PRIMARY KEY (id);


--
-- Name: flow_state flow_state_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.flow_state
    ADD CONSTRAINT flow_state_pkey PRIMARY KEY (id);


--
-- Name: identities identities_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.identities
    ADD CONSTRAINT identities_pkey PRIMARY KEY (id);


--
-- Name: identities identities_provider_id_provider_unique; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.identities
    ADD CONSTRAINT identities_provider_id_provider_unique UNIQUE (provider_id, provider);


--
-- Name: instances instances_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.instances
    ADD CONSTRAINT instances_pkey PRIMARY KEY (id);


--
-- Name: mfa_amr_claims mfa_amr_claims_session_id_authentication_method_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_amr_claims
    ADD CONSTRAINT mfa_amr_claims_session_id_authentication_method_pkey UNIQUE (session_id, authentication_method);


--
-- Name: mfa_challenges mfa_challenges_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_challenges
    ADD CONSTRAINT mfa_challenges_pkey PRIMARY KEY (id);


--
-- Name: mfa_factors mfa_factors_last_challenged_at_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_factors
    ADD CONSTRAINT mfa_factors_last_challenged_at_key UNIQUE (last_challenged_at);


--
-- Name: mfa_factors mfa_factors_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_factors
    ADD CONSTRAINT mfa_factors_pkey PRIMARY KEY (id);


--
-- Name: mfa_recovery_code_sets mfa_recovery_code_sets_mfa_factor_id_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_recovery_code_sets
    ADD CONSTRAINT mfa_recovery_code_sets_mfa_factor_id_key UNIQUE (mfa_factor_id);


--
-- Name: mfa_recovery_code_sets mfa_recovery_code_sets_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_recovery_code_sets
    ADD CONSTRAINT mfa_recovery_code_sets_pkey PRIMARY KEY (id);


--
-- Name: mfa_recovery_code_sets mfa_recovery_code_sets_user_id_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_recovery_code_sets
    ADD CONSTRAINT mfa_recovery_code_sets_user_id_key UNIQUE (user_id);


--
-- Name: mfa_recovery_codes mfa_recovery_codes_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_recovery_codes
    ADD CONSTRAINT mfa_recovery_codes_pkey PRIMARY KEY (id);


--
-- Name: oauth_authorizations oauth_authorizations_authorization_code_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_authorizations
    ADD CONSTRAINT oauth_authorizations_authorization_code_key UNIQUE (authorization_code);


--
-- Name: oauth_authorizations oauth_authorizations_authorization_id_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_authorizations
    ADD CONSTRAINT oauth_authorizations_authorization_id_key UNIQUE (authorization_id);


--
-- Name: oauth_authorizations oauth_authorizations_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_authorizations
    ADD CONSTRAINT oauth_authorizations_pkey PRIMARY KEY (id);


--
-- Name: oauth_client_states oauth_client_states_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_client_states
    ADD CONSTRAINT oauth_client_states_pkey PRIMARY KEY (id);


--
-- Name: oauth_clients oauth_clients_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_clients
    ADD CONSTRAINT oauth_clients_pkey PRIMARY KEY (id);


--
-- Name: oauth_consents oauth_consents_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_consents
    ADD CONSTRAINT oauth_consents_pkey PRIMARY KEY (id);


--
-- Name: oauth_consents oauth_consents_user_client_unique; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_consents
    ADD CONSTRAINT oauth_consents_user_client_unique UNIQUE (user_id, client_id);


--
-- Name: one_time_tokens one_time_tokens_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.one_time_tokens
    ADD CONSTRAINT one_time_tokens_pkey PRIMARY KEY (id);


--
-- Name: refresh_tokens refresh_tokens_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.refresh_tokens
    ADD CONSTRAINT refresh_tokens_pkey PRIMARY KEY (id);


--
-- Name: refresh_tokens refresh_tokens_token_unique; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.refresh_tokens
    ADD CONSTRAINT refresh_tokens_token_unique UNIQUE (token);


--
-- Name: saml_providers saml_providers_entity_id_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_providers
    ADD CONSTRAINT saml_providers_entity_id_key UNIQUE (entity_id);


--
-- Name: saml_providers saml_providers_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_providers
    ADD CONSTRAINT saml_providers_pkey PRIMARY KEY (id);


--
-- Name: saml_relay_states saml_relay_states_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_relay_states
    ADD CONSTRAINT saml_relay_states_pkey PRIMARY KEY (id);


--
-- Name: schema_migrations schema_migrations_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.schema_migrations
    ADD CONSTRAINT schema_migrations_pkey PRIMARY KEY (version);


--
-- Name: scim_tokens scim_tokens_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.scim_tokens
    ADD CONSTRAINT scim_tokens_pkey PRIMARY KEY (id);


--
-- Name: scim_users scim_users_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.scim_users
    ADD CONSTRAINT scim_users_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: sso_domains sso_domains_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sso_domains
    ADD CONSTRAINT sso_domains_pkey PRIMARY KEY (id);


--
-- Name: sso_providers sso_providers_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sso_providers
    ADD CONSTRAINT sso_providers_pkey PRIMARY KEY (id);


--
-- Name: users users_phone_key; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.users
    ADD CONSTRAINT users_phone_key UNIQUE (phone);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: webauthn_challenges webauthn_challenges_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.webauthn_challenges
    ADD CONSTRAINT webauthn_challenges_pkey PRIMARY KEY (id);


--
-- Name: webauthn_credentials webauthn_credentials_pkey; Type: CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.webauthn_credentials
    ADD CONSTRAINT webauthn_credentials_pkey PRIMARY KEY (id);


--
-- Name: bitacora_auditoria bitacora_auditoria_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacora_auditoria
    ADD CONSTRAINT bitacora_auditoria_pkey PRIMARY KEY (id_evento);


--
-- Name: categoria categoria_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categoria
    ADD CONSTRAINT categoria_nombre_key UNIQUE (nombre);


--
-- Name: categoria categoria_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categoria
    ADD CONSTRAINT categoria_pkey PRIMARY KEY (id_categoria);


--
-- Name: impuesto impuesto_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.impuesto
    ADD CONSTRAINT impuesto_nombre_key UNIQUE (nombre);


--
-- Name: impuesto impuesto_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.impuesto
    ADD CONSTRAINT impuesto_pkey PRIMARY KEY (id_impuesto);


--
-- Name: maquina maquina_codigo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maquina
    ADD CONSTRAINT maquina_codigo_key UNIQUE (codigo);


--
-- Name: maquina maquina_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maquina
    ADD CONSTRAINT maquina_pkey PRIMARY KEY (id_maquina);


--
-- Name: maquina_producto maquina_producto_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maquina_producto
    ADD CONSTRAINT maquina_producto_pkey PRIMARY KEY (id_maquina_producto);


--
-- Name: margen_ganancia margen_ganancia_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.margen_ganancia
    ADD CONSTRAINT margen_ganancia_nombre_key UNIQUE (nombre);


--
-- Name: margen_ganancia margen_ganancia_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.margen_ganancia
    ADD CONSTRAINT margen_ganancia_pkey PRIMARY KEY (id_margen);


--
-- Name: precio_tienda precio_tienda_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.precio_tienda
    ADD CONSTRAINT precio_tienda_pkey PRIMARY KEY (id_precio);


--
-- Name: producto producto_codigo_articulo_retail_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto
    ADD CONSTRAINT producto_codigo_articulo_retail_key UNIQUE (codigo_articulo_retail);


--
-- Name: producto producto_codigo_barras_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto
    ADD CONSTRAINT producto_codigo_barras_key UNIQUE (codigo_barras);


--
-- Name: producto producto_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto
    ADD CONSTRAINT producto_pkey PRIMARY KEY (id_producto);


--
-- Name: producto_proveedor producto_proveedor_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_proveedor
    ADD CONSTRAINT producto_proveedor_pkey PRIMARY KEY (id_producto_proveedor);


--
-- Name: proveedor proveedor_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.proveedor
    ADD CONSTRAINT proveedor_nombre_key UNIQUE (nombre);


--
-- Name: proveedor proveedor_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.proveedor
    ADD CONSTRAINT proveedor_pkey PRIMARY KEY (id_proveedor);


--
-- Name: recuperacion_contrasena recuperacion_contrasena_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.recuperacion_contrasena
    ADD CONSTRAINT recuperacion_contrasena_pkey PRIMARY KEY (id_recuperacion);


--
-- Name: relacion_abastecimiento relacion_abastecimiento_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.relacion_abastecimiento
    ADD CONSTRAINT relacion_abastecimiento_pkey PRIMARY KEY (id_relacion);


--
-- Name: respaldo respaldo_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.respaldo
    ADD CONSTRAINT respaldo_pkey PRIMARY KEY (id_respaldo);


--
-- Name: rol rol_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rol
    ADD CONSTRAINT rol_nombre_key UNIQUE (nombre);


--
-- Name: rol rol_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rol
    ADD CONSTRAINT rol_pkey PRIMARY KEY (id_rol);


--
-- Name: tienda tienda_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tienda
    ADD CONSTRAINT tienda_nombre_key UNIQUE (nombre);


--
-- Name: tienda tienda_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tienda
    ADD CONSTRAINT tienda_pkey PRIMARY KEY (id_tienda);


--
-- Name: maquina_producto uq_maquina_canal; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maquina_producto
    ADD CONSTRAINT uq_maquina_canal UNIQUE (id_maquina, canal);


--
-- Name: producto_proveedor uq_producto_proveedor; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_proveedor
    ADD CONSTRAINT uq_producto_proveedor UNIQUE (id_producto, id_proveedor);


--
-- Name: usuario_tienda uq_usuario_tienda; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario_tienda
    ADD CONSTRAINT uq_usuario_tienda UNIQUE (id_usuario, id_tienda);


--
-- Name: usuario usuario_correo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_correo_key UNIQUE (correo);


--
-- Name: usuario usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (id_usuario);


--
-- Name: usuario_tienda usuario_tienda_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario_tienda
    ADD CONSTRAINT usuario_tienda_pkey PRIMARY KEY (id_usuario_tienda);


--
-- Name: messages messages_payload_exclusive; Type: CHECK CONSTRAINT; Schema: realtime; Owner: supabase_realtime_admin
--

ALTER TABLE realtime.messages
    ADD CONSTRAINT messages_payload_exclusive CHECK (((payload IS NULL) OR (binary_payload IS NULL))) NOT VALID;


--
-- Name: messages messages_pkey; Type: CONSTRAINT; Schema: realtime; Owner: supabase_realtime_admin
--

ALTER TABLE ONLY realtime.messages
    ADD CONSTRAINT messages_pkey PRIMARY KEY (id, inserted_at);


--
-- Name: subscription pk_subscription; Type: CONSTRAINT; Schema: realtime; Owner: supabase_realtime_admin
--

ALTER TABLE ONLY realtime.subscription
    ADD CONSTRAINT pk_subscription PRIMARY KEY (id);


--
-- Name: schema_migrations schema_migrations_pkey; Type: CONSTRAINT; Schema: realtime; Owner: supabase_admin
--

ALTER TABLE ONLY realtime.schema_migrations
    ADD CONSTRAINT schema_migrations_pkey PRIMARY KEY (version);


--
-- Name: buckets_analytics buckets_analytics_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.buckets_analytics
    ADD CONSTRAINT buckets_analytics_pkey PRIMARY KEY (id);


--
-- Name: buckets buckets_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.buckets
    ADD CONSTRAINT buckets_pkey PRIMARY KEY (id);


--
-- Name: buckets_vectors buckets_vectors_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.buckets_vectors
    ADD CONSTRAINT buckets_vectors_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_name_key; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.migrations
    ADD CONSTRAINT migrations_name_key UNIQUE (name);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: objects objects_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.objects
    ADD CONSTRAINT objects_pkey PRIMARY KEY (id);


--
-- Name: s3_multipart_uploads_parts s3_multipart_uploads_parts_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads_parts
    ADD CONSTRAINT s3_multipart_uploads_parts_pkey PRIMARY KEY (id);


--
-- Name: s3_multipart_uploads s3_multipart_uploads_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads
    ADD CONSTRAINT s3_multipart_uploads_pkey PRIMARY KEY (id);


--
-- Name: vector_indexes vector_indexes_pkey; Type: CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.vector_indexes
    ADD CONSTRAINT vector_indexes_pkey PRIMARY KEY (id);


--
-- Name: audit_logs_instance_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX audit_logs_instance_id_idx ON auth.audit_log_entries USING btree (instance_id);


--
-- Name: confirmation_token_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX confirmation_token_idx ON auth.users USING btree (confirmation_token) WHERE ((confirmation_token)::text !~ '^[0-9 ]*$'::text);


--
-- Name: custom_oauth_providers_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX custom_oauth_providers_created_at_idx ON auth.custom_oauth_providers USING btree (created_at);


--
-- Name: custom_oauth_providers_enabled_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX custom_oauth_providers_enabled_idx ON auth.custom_oauth_providers USING btree (enabled);


--
-- Name: custom_oauth_providers_identifier_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX custom_oauth_providers_identifier_idx ON auth.custom_oauth_providers USING btree (identifier);


--
-- Name: custom_oauth_providers_provider_type_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX custom_oauth_providers_provider_type_idx ON auth.custom_oauth_providers USING btree (provider_type);


--
-- Name: email_change_token_current_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX email_change_token_current_idx ON auth.users USING btree (email_change_token_current) WHERE ((email_change_token_current)::text !~ '^[0-9 ]*$'::text);


--
-- Name: email_change_token_new_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX email_change_token_new_idx ON auth.users USING btree (email_change_token_new) WHERE ((email_change_token_new)::text !~ '^[0-9 ]*$'::text);


--
-- Name: factor_id_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX factor_id_created_at_idx ON auth.mfa_factors USING btree (user_id, created_at);


--
-- Name: flow_state_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX flow_state_created_at_idx ON auth.flow_state USING btree (created_at DESC);


--
-- Name: identities_email_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX identities_email_idx ON auth.identities USING btree (email text_pattern_ops);


--
-- Name: INDEX identities_email_idx; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON INDEX auth.identities_email_idx IS 'Auth: Ensures indexed queries on the email column';


--
-- Name: identities_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX identities_user_id_idx ON auth.identities USING btree (user_id);


--
-- Name: idx_auth_code; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX idx_auth_code ON auth.flow_state USING btree (auth_code);


--
-- Name: idx_oauth_client_states_created_at; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX idx_oauth_client_states_created_at ON auth.oauth_client_states USING btree (created_at);


--
-- Name: idx_user_id_auth_method; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX idx_user_id_auth_method ON auth.flow_state USING btree (user_id, authentication_method);


--
-- Name: idx_users_created_at_desc; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX idx_users_created_at_desc ON auth.users USING btree (created_at DESC);


--
-- Name: idx_users_email; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX idx_users_email ON auth.users USING btree (email);


--
-- Name: idx_users_last_sign_in_at_desc; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX idx_users_last_sign_in_at_desc ON auth.users USING btree (last_sign_in_at DESC);


--
-- Name: idx_users_name; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX idx_users_name ON auth.users USING btree (((raw_user_meta_data ->> 'name'::text))) WHERE ((raw_user_meta_data ->> 'name'::text) IS NOT NULL);


--
-- Name: mfa_challenge_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX mfa_challenge_created_at_idx ON auth.mfa_challenges USING btree (created_at DESC);


--
-- Name: mfa_factors_user_friendly_name_unique; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX mfa_factors_user_friendly_name_unique ON auth.mfa_factors USING btree (friendly_name, user_id) WHERE (TRIM(BOTH FROM friendly_name) <> ''::text);


--
-- Name: mfa_factors_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX mfa_factors_user_id_idx ON auth.mfa_factors USING btree (user_id);


--
-- Name: mfa_recovery_codes_set_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX mfa_recovery_codes_set_id_idx ON auth.mfa_recovery_codes USING btree (mfa_recovery_code_set_id);


--
-- Name: oauth_auth_pending_exp_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX oauth_auth_pending_exp_idx ON auth.oauth_authorizations USING btree (expires_at) WHERE (status = 'pending'::auth.oauth_authorization_status);


--
-- Name: oauth_clients_deleted_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX oauth_clients_deleted_at_idx ON auth.oauth_clients USING btree (deleted_at);


--
-- Name: oauth_consents_active_client_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX oauth_consents_active_client_idx ON auth.oauth_consents USING btree (client_id) WHERE (revoked_at IS NULL);


--
-- Name: oauth_consents_active_user_client_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX oauth_consents_active_user_client_idx ON auth.oauth_consents USING btree (user_id, client_id) WHERE (revoked_at IS NULL);


--
-- Name: oauth_consents_user_order_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX oauth_consents_user_order_idx ON auth.oauth_consents USING btree (user_id, granted_at DESC);


--
-- Name: one_time_tokens_relates_to_hash_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX one_time_tokens_relates_to_hash_idx ON auth.one_time_tokens USING hash (relates_to);


--
-- Name: one_time_tokens_token_hash_hash_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX one_time_tokens_token_hash_hash_idx ON auth.one_time_tokens USING hash (token_hash);


--
-- Name: one_time_tokens_user_id_token_type_key; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX one_time_tokens_user_id_token_type_key ON auth.one_time_tokens USING btree (user_id, token_type);


--
-- Name: reauthentication_token_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX reauthentication_token_idx ON auth.users USING btree (reauthentication_token) WHERE ((reauthentication_token)::text !~ '^[0-9 ]*$'::text);


--
-- Name: recovery_token_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX recovery_token_idx ON auth.users USING btree (recovery_token) WHERE ((recovery_token)::text !~ '^[0-9 ]*$'::text);


--
-- Name: refresh_tokens_instance_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_instance_id_idx ON auth.refresh_tokens USING btree (instance_id);


--
-- Name: refresh_tokens_instance_id_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_instance_id_user_id_idx ON auth.refresh_tokens USING btree (instance_id, user_id);


--
-- Name: refresh_tokens_parent_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_parent_idx ON auth.refresh_tokens USING btree (parent);


--
-- Name: refresh_tokens_session_id_revoked_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_session_id_revoked_idx ON auth.refresh_tokens USING btree (session_id, revoked);


--
-- Name: refresh_tokens_updated_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX refresh_tokens_updated_at_idx ON auth.refresh_tokens USING btree (updated_at DESC);


--
-- Name: saml_providers_sso_provider_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX saml_providers_sso_provider_id_idx ON auth.saml_providers USING btree (sso_provider_id);


--
-- Name: saml_relay_states_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX saml_relay_states_created_at_idx ON auth.saml_relay_states USING btree (created_at DESC);


--
-- Name: saml_relay_states_for_email_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX saml_relay_states_for_email_idx ON auth.saml_relay_states USING btree (for_email);


--
-- Name: saml_relay_states_sso_provider_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX saml_relay_states_sso_provider_id_idx ON auth.saml_relay_states USING btree (sso_provider_id);


--
-- Name: scim_tokens_expires_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX scim_tokens_expires_at_idx ON auth.scim_tokens USING btree (expires_at);


--
-- Name: scim_tokens_revoked_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX scim_tokens_revoked_at_idx ON auth.scim_tokens USING btree (revoked_at);


--
-- Name: scim_tokens_sso_provider_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX scim_tokens_sso_provider_id_idx ON auth.scim_tokens USING btree (sso_provider_id);


--
-- Name: scim_tokens_token_hash_key; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX scim_tokens_token_hash_key ON auth.scim_tokens USING btree (token_hash);


--
-- Name: scim_users_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX scim_users_created_at_idx ON auth.scim_users USING btree (sso_provider_id, created_at, id) WHERE (deleted_at IS NULL);


--
-- Name: scim_users_deleted_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX scim_users_deleted_at_idx ON auth.scim_users USING btree (deleted_at);


--
-- Name: scim_users_external_id_key; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX scim_users_external_id_key ON auth.scim_users USING btree (sso_provider_id, external_id) WHERE ((external_id IS NOT NULL) AND (deleted_at IS NULL));


--
-- Name: scim_users_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX scim_users_id_idx ON auth.scim_users USING btree (sso_provider_id, id) WHERE (deleted_at IS NULL);


--
-- Name: scim_users_sso_provider_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX scim_users_sso_provider_id_idx ON auth.scim_users USING btree (sso_provider_id);


--
-- Name: scim_users_updated_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX scim_users_updated_at_idx ON auth.scim_users USING btree (sso_provider_id, updated_at, id) WHERE (deleted_at IS NULL);


--
-- Name: scim_users_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX scim_users_user_id_idx ON auth.scim_users USING btree (user_id);


--
-- Name: scim_users_user_name_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX scim_users_user_name_idx ON auth.scim_users USING btree (sso_provider_id, user_name COLLATE "C", id) WHERE (deleted_at IS NULL);


--
-- Name: scim_users_user_name_key; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX scim_users_user_name_key ON auth.scim_users USING btree (sso_provider_id, user_name) WHERE (deleted_at IS NULL);


--
-- Name: sessions_not_after_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX sessions_not_after_idx ON auth.sessions USING btree (not_after DESC);


--
-- Name: sessions_oauth_client_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX sessions_oauth_client_id_idx ON auth.sessions USING btree (oauth_client_id);


--
-- Name: sessions_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX sessions_user_id_idx ON auth.sessions USING btree (user_id);


--
-- Name: sso_domains_domain_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX sso_domains_domain_idx ON auth.sso_domains USING btree (lower(domain));


--
-- Name: sso_domains_sso_provider_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX sso_domains_sso_provider_id_idx ON auth.sso_domains USING btree (sso_provider_id);


--
-- Name: sso_providers_resource_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX sso_providers_resource_id_idx ON auth.sso_providers USING btree (lower(resource_id));


--
-- Name: sso_providers_resource_id_pattern_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX sso_providers_resource_id_pattern_idx ON auth.sso_providers USING btree (resource_id text_pattern_ops);


--
-- Name: unique_phone_factor_per_user; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX unique_phone_factor_per_user ON auth.mfa_factors USING btree (user_id, phone);


--
-- Name: user_id_created_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX user_id_created_at_idx ON auth.sessions USING btree (user_id, created_at);


--
-- Name: users_email_partial_key; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX users_email_partial_key ON auth.users USING btree (email) WHERE (is_sso_user = false);


--
-- Name: INDEX users_email_partial_key; Type: COMMENT; Schema: auth; Owner: supabase_auth_admin
--

COMMENT ON INDEX auth.users_email_partial_key IS 'Auth: A partial unique index that applies only when is_sso_user is false';


--
-- Name: users_instance_id_email_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX users_instance_id_email_idx ON auth.users USING btree (instance_id, lower((email)::text));


--
-- Name: users_instance_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX users_instance_id_idx ON auth.users USING btree (instance_id);


--
-- Name: users_is_anonymous_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX users_is_anonymous_idx ON auth.users USING btree (is_anonymous);


--
-- Name: webauthn_challenges_expires_at_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX webauthn_challenges_expires_at_idx ON auth.webauthn_challenges USING btree (expires_at);


--
-- Name: webauthn_challenges_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX webauthn_challenges_user_id_idx ON auth.webauthn_challenges USING btree (user_id);


--
-- Name: webauthn_credentials_credential_id_key; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE UNIQUE INDEX webauthn_credentials_credential_id_key ON auth.webauthn_credentials USING btree (credential_id);


--
-- Name: webauthn_credentials_user_id_idx; Type: INDEX; Schema: auth; Owner: supabase_auth_admin
--

CREATE INDEX webauthn_credentials_user_id_idx ON auth.webauthn_credentials USING btree (user_id);


--
-- Name: idx_maquina_producto_maquina; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_maquina_producto_maquina ON public.maquina_producto USING btree (id_maquina);


--
-- Name: idx_maquina_producto_producto; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_maquina_producto_producto ON public.maquina_producto USING btree (id_producto);


--
-- Name: idx_maquina_tienda; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_maquina_tienda ON public.maquina USING btree (id_tienda);


--
-- Name: idx_recuperacion_token_hash; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_recuperacion_token_hash ON public.recuperacion_contrasena USING btree (token_hash);


--
-- Name: idx_relacion_maquina_destino; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_relacion_maquina_destino ON public.relacion_abastecimiento USING btree (id_maquina_destino);


--
-- Name: idx_relacion_tienda_destino; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_relacion_tienda_destino ON public.relacion_abastecimiento USING btree (id_tienda_destino);


--
-- Name: uq_precio_activo_producto_tienda; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX uq_precio_activo_producto_tienda ON public.precio_tienda USING btree (id_producto, id_tienda) WHERE ((estado = true) AND (fecha_fin IS NULL));


--
-- Name: uq_relacion_maquina_destino; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX uq_relacion_maquina_destino ON public.relacion_abastecimiento USING btree (id_tienda_origen, id_maquina_destino) WHERE (id_maquina_destino IS NOT NULL);


--
-- Name: uq_relacion_tienda_destino; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX uq_relacion_tienda_destino ON public.relacion_abastecimiento USING btree (id_tienda_origen, id_tienda_destino) WHERE (id_tienda_destino IS NOT NULL);


--
-- Name: ix_realtime_subscription_entity; Type: INDEX; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE INDEX ix_realtime_subscription_entity ON realtime.subscription USING btree (entity);


--
-- Name: messages_inserted_at_topic_index; Type: INDEX; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE INDEX messages_inserted_at_topic_index ON ONLY realtime.messages USING btree (inserted_at DESC, topic) WHERE ((extension = 'broadcast'::text) AND (private IS TRUE));


--
-- Name: subscription_subscription_id_entity_filters_action_filter_selec; Type: INDEX; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE UNIQUE INDEX subscription_subscription_id_entity_filters_action_filter_selec ON realtime.subscription USING btree (subscription_id, entity, filters, action_filter, COALESCE(selected_columns, '{}'::text[]));


--
-- Name: bname; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE UNIQUE INDEX bname ON storage.buckets USING btree (name);


--
-- Name: buckets_analytics_unique_name_idx; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE UNIQUE INDEX buckets_analytics_unique_name_idx ON storage.buckets_analytics USING btree (name) WHERE (deleted_at IS NULL);


--
-- Name: idx_multipart_uploads_list; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX idx_multipart_uploads_list ON storage.s3_multipart_uploads USING btree (bucket_id, key, created_at);


--
-- Name: idx_objects_bucket_id_name; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX idx_objects_bucket_id_name ON storage.objects USING btree (bucket_id, name COLLATE "C");


--
-- Name: idx_objects_bucket_id_name_lower; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX idx_objects_bucket_id_name_lower ON storage.objects USING btree (bucket_id, lower(name) COLLATE "C");


--
-- Name: idx_objects_current_version; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE UNIQUE INDEX idx_objects_current_version ON storage.objects USING btree (bucket_id, name COLLATE "C") WHERE (archived_at IS NULL);


--
-- Name: idx_objects_delete_markers; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX idx_objects_delete_markers ON storage.objects USING btree (bucket_id, name COLLATE "C") WHERE is_delete_marker;


--
-- Name: idx_objects_null_version; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE UNIQUE INDEX idx_objects_null_version ON storage.objects USING btree (bucket_id, name COLLATE "C") WHERE (NOT is_versioned);


--
-- Name: name_prefix_search; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE INDEX name_prefix_search ON storage.objects USING btree (name text_pattern_ops);


--
-- Name: objects_bucket_id_name_version_key; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE UNIQUE INDEX objects_bucket_id_name_version_key ON storage.objects USING btree (bucket_id, name COLLATE "C", version) NULLS NOT DISTINCT;


--
-- Name: vector_indexes_name_bucket_id_idx; Type: INDEX; Schema: storage; Owner: supabase_storage_admin
--

CREATE UNIQUE INDEX vector_indexes_name_bucket_id_idx ON storage.vector_indexes USING btree (name, bucket_id);


--
-- Name: precio_tienda trg_calcular_precio_tienda; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_calcular_precio_tienda BEFORE INSERT OR UPDATE OF id_producto, id_margen, costo ON public.precio_tienda FOR EACH ROW EXECUTE FUNCTION public.fn_calcular_precio_tienda();


--
-- Name: precio_tienda trg_cerrar_precio_anterior; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_cerrar_precio_anterior BEFORE INSERT ON public.precio_tienda FOR EACH ROW EXECUTE FUNCTION public.fn_cerrar_precio_anterior();


--
-- Name: subscription tr_check_filters; Type: TRIGGER; Schema: realtime; Owner: supabase_realtime_admin
--

CREATE TRIGGER tr_check_filters BEFORE INSERT OR UPDATE ON realtime.subscription FOR EACH ROW EXECUTE FUNCTION realtime.subscription_check_filters();


--
-- Name: buckets enforce_bucket_name_length_trigger; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER enforce_bucket_name_length_trigger BEFORE INSERT OR UPDATE OF name ON storage.buckets FOR EACH ROW EXECUTE FUNCTION storage.enforce_bucket_name_length();


--
-- Name: buckets protect_bucket_control_insert; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER protect_bucket_control_insert BEFORE INSERT ON storage.buckets FOR EACH ROW EXECUTE FUNCTION storage.protect_bucket_control_columns('service_role');


--
-- Name: buckets protect_bucket_control_update; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER protect_bucket_control_update BEFORE UPDATE OF lifecycle_configuration, lifecycle_configuration_generation ON storage.buckets FOR EACH ROW EXECUTE FUNCTION storage.protect_bucket_control_columns();


--
-- Name: buckets protect_bucket_control_update_role; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER protect_bucket_control_update_role AFTER UPDATE OF lifecycle_configuration, lifecycle_configuration_generation ON storage.buckets FOR EACH ROW EXECUTE FUNCTION storage.enforce_bucket_lifecycle_service_role('service_role');


--
-- Name: buckets protect_buckets_delete; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER protect_buckets_delete BEFORE DELETE ON storage.buckets FOR EACH STATEMENT EXECUTE FUNCTION storage.protect_delete();


--
-- Name: objects protect_objects_delete; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER protect_objects_delete BEFORE DELETE ON storage.objects FOR EACH STATEMENT EXECUTE FUNCTION storage.protect_delete();


--
-- Name: objects update_objects_updated_at; Type: TRIGGER; Schema: storage; Owner: supabase_storage_admin
--

CREATE TRIGGER update_objects_updated_at BEFORE UPDATE ON storage.objects FOR EACH ROW EXECUTE FUNCTION storage.update_updated_at_column();


--
-- Name: identities identities_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.identities
    ADD CONSTRAINT identities_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: mfa_amr_claims mfa_amr_claims_session_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_amr_claims
    ADD CONSTRAINT mfa_amr_claims_session_id_fkey FOREIGN KEY (session_id) REFERENCES auth.sessions(id) ON DELETE CASCADE;


--
-- Name: mfa_challenges mfa_challenges_auth_factor_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_challenges
    ADD CONSTRAINT mfa_challenges_auth_factor_id_fkey FOREIGN KEY (factor_id) REFERENCES auth.mfa_factors(id) ON DELETE CASCADE;


--
-- Name: mfa_factors mfa_factors_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_factors
    ADD CONSTRAINT mfa_factors_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: mfa_recovery_code_sets mfa_recovery_code_sets_mfa_factor_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_recovery_code_sets
    ADD CONSTRAINT mfa_recovery_code_sets_mfa_factor_id_fkey FOREIGN KEY (mfa_factor_id) REFERENCES auth.mfa_factors(id) ON DELETE CASCADE;


--
-- Name: mfa_recovery_code_sets mfa_recovery_code_sets_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_recovery_code_sets
    ADD CONSTRAINT mfa_recovery_code_sets_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: mfa_recovery_codes mfa_recovery_codes_mfa_recovery_code_set_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.mfa_recovery_codes
    ADD CONSTRAINT mfa_recovery_codes_mfa_recovery_code_set_id_fkey FOREIGN KEY (mfa_recovery_code_set_id) REFERENCES auth.mfa_recovery_code_sets(id) ON DELETE CASCADE;


--
-- Name: oauth_authorizations oauth_authorizations_client_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_authorizations
    ADD CONSTRAINT oauth_authorizations_client_id_fkey FOREIGN KEY (client_id) REFERENCES auth.oauth_clients(id) ON DELETE CASCADE;


--
-- Name: oauth_authorizations oauth_authorizations_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_authorizations
    ADD CONSTRAINT oauth_authorizations_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: oauth_consents oauth_consents_client_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_consents
    ADD CONSTRAINT oauth_consents_client_id_fkey FOREIGN KEY (client_id) REFERENCES auth.oauth_clients(id) ON DELETE CASCADE;


--
-- Name: oauth_consents oauth_consents_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.oauth_consents
    ADD CONSTRAINT oauth_consents_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: one_time_tokens one_time_tokens_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.one_time_tokens
    ADD CONSTRAINT one_time_tokens_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: refresh_tokens refresh_tokens_session_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.refresh_tokens
    ADD CONSTRAINT refresh_tokens_session_id_fkey FOREIGN KEY (session_id) REFERENCES auth.sessions(id) ON DELETE CASCADE;


--
-- Name: saml_providers saml_providers_sso_provider_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_providers
    ADD CONSTRAINT saml_providers_sso_provider_id_fkey FOREIGN KEY (sso_provider_id) REFERENCES auth.sso_providers(id) ON DELETE CASCADE;


--
-- Name: saml_relay_states saml_relay_states_flow_state_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_relay_states
    ADD CONSTRAINT saml_relay_states_flow_state_id_fkey FOREIGN KEY (flow_state_id) REFERENCES auth.flow_state(id) ON DELETE CASCADE;


--
-- Name: saml_relay_states saml_relay_states_sso_provider_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.saml_relay_states
    ADD CONSTRAINT saml_relay_states_sso_provider_id_fkey FOREIGN KEY (sso_provider_id) REFERENCES auth.sso_providers(id) ON DELETE CASCADE;


--
-- Name: scim_tokens scim_tokens_sso_provider_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.scim_tokens
    ADD CONSTRAINT scim_tokens_sso_provider_id_fkey FOREIGN KEY (sso_provider_id) REFERENCES auth.sso_providers(id) ON DELETE CASCADE;


--
-- Name: scim_users scim_users_sso_provider_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.scim_users
    ADD CONSTRAINT scim_users_sso_provider_id_fkey FOREIGN KEY (sso_provider_id) REFERENCES auth.sso_providers(id) ON DELETE CASCADE;


--
-- Name: scim_users scim_users_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.scim_users
    ADD CONSTRAINT scim_users_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE SET NULL;


--
-- Name: sessions sessions_oauth_client_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sessions
    ADD CONSTRAINT sessions_oauth_client_id_fkey FOREIGN KEY (oauth_client_id) REFERENCES auth.oauth_clients(id) ON DELETE CASCADE;


--
-- Name: sessions sessions_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sessions
    ADD CONSTRAINT sessions_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: sso_domains sso_domains_sso_provider_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.sso_domains
    ADD CONSTRAINT sso_domains_sso_provider_id_fkey FOREIGN KEY (sso_provider_id) REFERENCES auth.sso_providers(id) ON DELETE CASCADE;


--
-- Name: webauthn_challenges webauthn_challenges_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.webauthn_challenges
    ADD CONSTRAINT webauthn_challenges_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: webauthn_credentials webauthn_credentials_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE ONLY auth.webauthn_credentials
    ADD CONSTRAINT webauthn_credentials_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: bitacora_auditoria fk_bitacora_tienda; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacora_auditoria
    ADD CONSTRAINT fk_bitacora_tienda FOREIGN KEY (id_tienda) REFERENCES public.tienda(id_tienda);


--
-- Name: bitacora_auditoria fk_bitacora_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacora_auditoria
    ADD CONSTRAINT fk_bitacora_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario);


--
-- Name: maquina_producto fk_maquina_producto_maquina; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maquina_producto
    ADD CONSTRAINT fk_maquina_producto_maquina FOREIGN KEY (id_maquina) REFERENCES public.maquina(id_maquina);


--
-- Name: maquina_producto fk_maquina_producto_producto; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maquina_producto
    ADD CONSTRAINT fk_maquina_producto_producto FOREIGN KEY (id_producto) REFERENCES public.producto(id_producto);


--
-- Name: maquina fk_maquina_tienda; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maquina
    ADD CONSTRAINT fk_maquina_tienda FOREIGN KEY (id_tienda) REFERENCES public.tienda(id_tienda);


--
-- Name: precio_tienda fk_precio_margen; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.precio_tienda
    ADD CONSTRAINT fk_precio_margen FOREIGN KEY (id_margen) REFERENCES public.margen_ganancia(id_margen);


--
-- Name: precio_tienda fk_precio_producto; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.precio_tienda
    ADD CONSTRAINT fk_precio_producto FOREIGN KEY (id_producto) REFERENCES public.producto(id_producto);


--
-- Name: precio_tienda fk_precio_tienda; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.precio_tienda
    ADD CONSTRAINT fk_precio_tienda FOREIGN KEY (id_tienda) REFERENCES public.tienda(id_tienda);


--
-- Name: producto fk_producto_categoria; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto
    ADD CONSTRAINT fk_producto_categoria FOREIGN KEY (id_categoria) REFERENCES public.categoria(id_categoria);


--
-- Name: producto fk_producto_impuesto; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto
    ADD CONSTRAINT fk_producto_impuesto FOREIGN KEY (id_impuesto) REFERENCES public.impuesto(id_impuesto);


--
-- Name: producto_proveedor fk_producto_proveedor_producto; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_proveedor
    ADD CONSTRAINT fk_producto_proveedor_producto FOREIGN KEY (id_producto) REFERENCES public.producto(id_producto);


--
-- Name: producto_proveedor fk_producto_proveedor_proveedor; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.producto_proveedor
    ADD CONSTRAINT fk_producto_proveedor_proveedor FOREIGN KEY (id_proveedor) REFERENCES public.proveedor(id_proveedor);


--
-- Name: recuperacion_contrasena fk_recuperacion_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.recuperacion_contrasena
    ADD CONSTRAINT fk_recuperacion_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario) ON DELETE CASCADE;


--
-- Name: relacion_abastecimiento fk_relacion_maquina_destino; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.relacion_abastecimiento
    ADD CONSTRAINT fk_relacion_maquina_destino FOREIGN KEY (id_maquina_destino) REFERENCES public.maquina(id_maquina);


--
-- Name: relacion_abastecimiento fk_relacion_tienda_destino; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.relacion_abastecimiento
    ADD CONSTRAINT fk_relacion_tienda_destino FOREIGN KEY (id_tienda_destino) REFERENCES public.tienda(id_tienda);


--
-- Name: relacion_abastecimiento fk_relacion_tienda_origen; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.relacion_abastecimiento
    ADD CONSTRAINT fk_relacion_tienda_origen FOREIGN KEY (id_tienda_origen) REFERENCES public.tienda(id_tienda);


--
-- Name: respaldo fk_respaldo_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.respaldo
    ADD CONSTRAINT fk_respaldo_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario) ON DELETE SET NULL;


--
-- Name: usuario fk_usuario_rol; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT fk_usuario_rol FOREIGN KEY (id_rol) REFERENCES public.rol(id_rol);


--
-- Name: usuario_tienda fk_usuario_tienda_tienda; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario_tienda
    ADD CONSTRAINT fk_usuario_tienda_tienda FOREIGN KEY (id_tienda) REFERENCES public.tienda(id_tienda);


--
-- Name: usuario_tienda fk_usuario_tienda_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario_tienda
    ADD CONSTRAINT fk_usuario_tienda_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario);


--
-- Name: objects objects_bucketId_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.objects
    ADD CONSTRAINT "objects_bucketId_fkey" FOREIGN KEY (bucket_id) REFERENCES storage.buckets(id);


--
-- Name: s3_multipart_uploads s3_multipart_uploads_bucket_id_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads
    ADD CONSTRAINT s3_multipart_uploads_bucket_id_fkey FOREIGN KEY (bucket_id) REFERENCES storage.buckets(id);


--
-- Name: s3_multipart_uploads_parts s3_multipart_uploads_parts_bucket_id_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads_parts
    ADD CONSTRAINT s3_multipart_uploads_parts_bucket_id_fkey FOREIGN KEY (bucket_id) REFERENCES storage.buckets(id);


--
-- Name: s3_multipart_uploads_parts s3_multipart_uploads_parts_upload_id_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.s3_multipart_uploads_parts
    ADD CONSTRAINT s3_multipart_uploads_parts_upload_id_fkey FOREIGN KEY (upload_id) REFERENCES storage.s3_multipart_uploads(id) ON DELETE CASCADE;


--
-- Name: vector_indexes vector_indexes_bucket_id_fkey; Type: FK CONSTRAINT; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE ONLY storage.vector_indexes
    ADD CONSTRAINT vector_indexes_bucket_id_fkey FOREIGN KEY (bucket_id) REFERENCES storage.buckets_vectors(id);


--
-- Name: audit_log_entries; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.audit_log_entries ENABLE ROW LEVEL SECURITY;

--
-- Name: flow_state; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.flow_state ENABLE ROW LEVEL SECURITY;

--
-- Name: identities; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.identities ENABLE ROW LEVEL SECURITY;

--
-- Name: instances; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.instances ENABLE ROW LEVEL SECURITY;

--
-- Name: mfa_amr_claims; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.mfa_amr_claims ENABLE ROW LEVEL SECURITY;

--
-- Name: mfa_challenges; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.mfa_challenges ENABLE ROW LEVEL SECURITY;

--
-- Name: mfa_factors; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.mfa_factors ENABLE ROW LEVEL SECURITY;

--
-- Name: one_time_tokens; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.one_time_tokens ENABLE ROW LEVEL SECURITY;

--
-- Name: refresh_tokens; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.refresh_tokens ENABLE ROW LEVEL SECURITY;

--
-- Name: saml_providers; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.saml_providers ENABLE ROW LEVEL SECURITY;

--
-- Name: saml_relay_states; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.saml_relay_states ENABLE ROW LEVEL SECURITY;

--
-- Name: schema_migrations; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.schema_migrations ENABLE ROW LEVEL SECURITY;

--
-- Name: sessions; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.sessions ENABLE ROW LEVEL SECURITY;

--
-- Name: sso_domains; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.sso_domains ENABLE ROW LEVEL SECURITY;

--
-- Name: sso_providers; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.sso_providers ENABLE ROW LEVEL SECURITY;

--
-- Name: users; Type: ROW SECURITY; Schema: auth; Owner: supabase_auth_admin
--

ALTER TABLE auth.users ENABLE ROW LEVEL SECURITY;

--
-- Name: bitacora_auditoria; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.bitacora_auditoria ENABLE ROW LEVEL SECURITY;

--
-- Name: categoria; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.categoria ENABLE ROW LEVEL SECURITY;

--
-- Name: impuesto; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.impuesto ENABLE ROW LEVEL SECURITY;

--
-- Name: maquina; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.maquina ENABLE ROW LEVEL SECURITY;

--
-- Name: maquina_producto; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.maquina_producto ENABLE ROW LEVEL SECURITY;

--
-- Name: margen_ganancia; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.margen_ganancia ENABLE ROW LEVEL SECURITY;

--
-- Name: precio_tienda; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.precio_tienda ENABLE ROW LEVEL SECURITY;

--
-- Name: producto; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.producto ENABLE ROW LEVEL SECURITY;

--
-- Name: producto_proveedor; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.producto_proveedor ENABLE ROW LEVEL SECURITY;

--
-- Name: proveedor; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.proveedor ENABLE ROW LEVEL SECURITY;

--
-- Name: recuperacion_contrasena; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.recuperacion_contrasena ENABLE ROW LEVEL SECURITY;

--
-- Name: relacion_abastecimiento; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.relacion_abastecimiento ENABLE ROW LEVEL SECURITY;

--
-- Name: respaldo; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.respaldo ENABLE ROW LEVEL SECURITY;

--
-- Name: rol; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.rol ENABLE ROW LEVEL SECURITY;

--
-- Name: tienda; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.tienda ENABLE ROW LEVEL SECURITY;

--
-- Name: usuario; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.usuario ENABLE ROW LEVEL SECURITY;

--
-- Name: usuario_tienda; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.usuario_tienda ENABLE ROW LEVEL SECURITY;

--
-- Name: messages; Type: ROW SECURITY; Schema: realtime; Owner: supabase_realtime_admin
--

ALTER TABLE realtime.messages ENABLE ROW LEVEL SECURITY;

--
-- Name: buckets; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.buckets ENABLE ROW LEVEL SECURITY;

--
-- Name: buckets_analytics; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.buckets_analytics ENABLE ROW LEVEL SECURITY;

--
-- Name: buckets_vectors; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.buckets_vectors ENABLE ROW LEVEL SECURITY;

--
-- Name: migrations; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.migrations ENABLE ROW LEVEL SECURITY;

--
-- Name: objects; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.objects ENABLE ROW LEVEL SECURITY;

--
-- Name: s3_multipart_uploads; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.s3_multipart_uploads ENABLE ROW LEVEL SECURITY;

--
-- Name: s3_multipart_uploads_parts; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.s3_multipart_uploads_parts ENABLE ROW LEVEL SECURITY;

--
-- Name: vector_indexes; Type: ROW SECURITY; Schema: storage; Owner: supabase_storage_admin
--

ALTER TABLE storage.vector_indexes ENABLE ROW LEVEL SECURITY;

--
-- Name: supabase_realtime; Type: PUBLICATION; Schema: -; Owner: postgres
--

CREATE PUBLICATION supabase_realtime WITH (publish = 'insert, update, delete, truncate');


ALTER PUBLICATION supabase_realtime OWNER TO postgres;

--
-- Name: SCHEMA auth; Type: ACL; Schema: -; Owner: supabase_admin
--

GRANT USAGE ON SCHEMA auth TO anon;
GRANT USAGE ON SCHEMA auth TO authenticated;
GRANT USAGE ON SCHEMA auth TO service_role;
GRANT ALL ON SCHEMA auth TO supabase_auth_admin;
GRANT ALL ON SCHEMA auth TO dashboard_user;
GRANT USAGE ON SCHEMA auth TO postgres;


--
-- Name: SCHEMA extensions; Type: ACL; Schema: -; Owner: postgres
--

GRANT USAGE ON SCHEMA extensions TO anon;
GRANT USAGE ON SCHEMA extensions TO authenticated;
GRANT USAGE ON SCHEMA extensions TO service_role;
GRANT ALL ON SCHEMA extensions TO dashboard_user;


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: pg_database_owner
--

GRANT USAGE ON SCHEMA public TO postgres;
GRANT USAGE ON SCHEMA public TO anon;
GRANT USAGE ON SCHEMA public TO authenticated;
GRANT USAGE ON SCHEMA public TO service_role;


--
-- Name: SCHEMA realtime; Type: ACL; Schema: -; Owner: supabase_admin
--

GRANT USAGE ON SCHEMA realtime TO postgres WITH GRANT OPTION;
GRANT USAGE ON SCHEMA realtime TO anon;
GRANT USAGE ON SCHEMA realtime TO service_role;
GRANT ALL ON SCHEMA realtime TO supabase_realtime_admin;
GRANT USAGE ON SCHEMA realtime TO authenticated;


--
-- Name: SCHEMA storage; Type: ACL; Schema: -; Owner: supabase_admin
--

GRANT USAGE ON SCHEMA storage TO postgres WITH GRANT OPTION;
GRANT USAGE ON SCHEMA storage TO anon;
GRANT USAGE ON SCHEMA storage TO authenticated;
GRANT USAGE ON SCHEMA storage TO service_role;
GRANT ALL ON SCHEMA storage TO supabase_storage_admin WITH GRANT OPTION;
GRANT ALL ON SCHEMA storage TO dashboard_user;


--
-- Name: SCHEMA vault; Type: ACL; Schema: -; Owner: supabase_admin
--

GRANT USAGE ON SCHEMA vault TO postgres WITH GRANT OPTION;
GRANT USAGE ON SCHEMA vault TO service_role;


--
-- Name: FUNCTION email(); Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON FUNCTION auth.email() TO dashboard_user;


--
-- Name: FUNCTION jwt(); Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON FUNCTION auth.jwt() TO postgres;
GRANT ALL ON FUNCTION auth.jwt() TO dashboard_user;


--
-- Name: FUNCTION role(); Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON FUNCTION auth.role() TO dashboard_user;


--
-- Name: FUNCTION uid(); Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON FUNCTION auth.uid() TO dashboard_user;


--
-- Name: FUNCTION armor(bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.armor(bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.armor(bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.armor(bytea) TO dashboard_user;


--
-- Name: FUNCTION armor(bytea, text[], text[]); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.armor(bytea, text[], text[]) FROM postgres;
GRANT ALL ON FUNCTION extensions.armor(bytea, text[], text[]) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.armor(bytea, text[], text[]) TO dashboard_user;


--
-- Name: FUNCTION crypt(text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.crypt(text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.crypt(text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.crypt(text, text) TO dashboard_user;


--
-- Name: FUNCTION dearmor(text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.dearmor(text) FROM postgres;
GRANT ALL ON FUNCTION extensions.dearmor(text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.dearmor(text) TO dashboard_user;


--
-- Name: FUNCTION decrypt(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.decrypt(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.decrypt(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.decrypt(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION decrypt_iv(bytea, bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.decrypt_iv(bytea, bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.decrypt_iv(bytea, bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.decrypt_iv(bytea, bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION digest(bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.digest(bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.digest(bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.digest(bytea, text) TO dashboard_user;


--
-- Name: FUNCTION digest(text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.digest(text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.digest(text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.digest(text, text) TO dashboard_user;


--
-- Name: FUNCTION encrypt(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.encrypt(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.encrypt(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.encrypt(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION encrypt_iv(bytea, bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.encrypt_iv(bytea, bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.encrypt_iv(bytea, bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.encrypt_iv(bytea, bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION gen_random_bytes(integer); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.gen_random_bytes(integer) FROM postgres;
GRANT ALL ON FUNCTION extensions.gen_random_bytes(integer) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.gen_random_bytes(integer) TO dashboard_user;


--
-- Name: FUNCTION gen_random_uuid(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.gen_random_uuid() FROM postgres;
GRANT ALL ON FUNCTION extensions.gen_random_uuid() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.gen_random_uuid() TO dashboard_user;


--
-- Name: FUNCTION gen_salt(text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.gen_salt(text) FROM postgres;
GRANT ALL ON FUNCTION extensions.gen_salt(text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.gen_salt(text) TO dashboard_user;


--
-- Name: FUNCTION gen_salt(text, integer); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.gen_salt(text, integer) FROM postgres;
GRANT ALL ON FUNCTION extensions.gen_salt(text, integer) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.gen_salt(text, integer) TO dashboard_user;


--
-- Name: FUNCTION grant_pg_cron_access(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

REVOKE ALL ON FUNCTION extensions.grant_pg_cron_access() FROM supabase_admin;
GRANT ALL ON FUNCTION extensions.grant_pg_cron_access() TO supabase_admin WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.grant_pg_cron_access() TO dashboard_user;


--
-- Name: FUNCTION grant_pg_graphql_access(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

GRANT ALL ON FUNCTION extensions.grant_pg_graphql_access() TO postgres WITH GRANT OPTION;


--
-- Name: FUNCTION grant_pg_net_access(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

REVOKE ALL ON FUNCTION extensions.grant_pg_net_access() FROM supabase_admin;
GRANT ALL ON FUNCTION extensions.grant_pg_net_access() TO supabase_admin WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.grant_pg_net_access() TO dashboard_user;


--
-- Name: FUNCTION hmac(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.hmac(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.hmac(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.hmac(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION hmac(text, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.hmac(text, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.hmac(text, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.hmac(text, text, text) TO dashboard_user;


--
-- Name: FUNCTION pg_stat_statements(showtext boolean, OUT userid oid, OUT dbid oid, OUT toplevel boolean, OUT queryid bigint, OUT query text, OUT plans bigint, OUT total_plan_time double precision, OUT min_plan_time double precision, OUT max_plan_time double precision, OUT mean_plan_time double precision, OUT stddev_plan_time double precision, OUT calls bigint, OUT total_exec_time double precision, OUT min_exec_time double precision, OUT max_exec_time double precision, OUT mean_exec_time double precision, OUT stddev_exec_time double precision, OUT rows bigint, OUT shared_blks_hit bigint, OUT shared_blks_read bigint, OUT shared_blks_dirtied bigint, OUT shared_blks_written bigint, OUT local_blks_hit bigint, OUT local_blks_read bigint, OUT local_blks_dirtied bigint, OUT local_blks_written bigint, OUT temp_blks_read bigint, OUT temp_blks_written bigint, OUT shared_blk_read_time double precision, OUT shared_blk_write_time double precision, OUT local_blk_read_time double precision, OUT local_blk_write_time double precision, OUT temp_blk_read_time double precision, OUT temp_blk_write_time double precision, OUT wal_records bigint, OUT wal_fpi bigint, OUT wal_bytes numeric, OUT jit_functions bigint, OUT jit_generation_time double precision, OUT jit_inlining_count bigint, OUT jit_inlining_time double precision, OUT jit_optimization_count bigint, OUT jit_optimization_time double precision, OUT jit_emission_count bigint, OUT jit_emission_time double precision, OUT jit_deform_count bigint, OUT jit_deform_time double precision, OUT stats_since timestamp with time zone, OUT minmax_stats_since timestamp with time zone); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pg_stat_statements(showtext boolean, OUT userid oid, OUT dbid oid, OUT toplevel boolean, OUT queryid bigint, OUT query text, OUT plans bigint, OUT total_plan_time double precision, OUT min_plan_time double precision, OUT max_plan_time double precision, OUT mean_plan_time double precision, OUT stddev_plan_time double precision, OUT calls bigint, OUT total_exec_time double precision, OUT min_exec_time double precision, OUT max_exec_time double precision, OUT mean_exec_time double precision, OUT stddev_exec_time double precision, OUT rows bigint, OUT shared_blks_hit bigint, OUT shared_blks_read bigint, OUT shared_blks_dirtied bigint, OUT shared_blks_written bigint, OUT local_blks_hit bigint, OUT local_blks_read bigint, OUT local_blks_dirtied bigint, OUT local_blks_written bigint, OUT temp_blks_read bigint, OUT temp_blks_written bigint, OUT shared_blk_read_time double precision, OUT shared_blk_write_time double precision, OUT local_blk_read_time double precision, OUT local_blk_write_time double precision, OUT temp_blk_read_time double precision, OUT temp_blk_write_time double precision, OUT wal_records bigint, OUT wal_fpi bigint, OUT wal_bytes numeric, OUT jit_functions bigint, OUT jit_generation_time double precision, OUT jit_inlining_count bigint, OUT jit_inlining_time double precision, OUT jit_optimization_count bigint, OUT jit_optimization_time double precision, OUT jit_emission_count bigint, OUT jit_emission_time double precision, OUT jit_deform_count bigint, OUT jit_deform_time double precision, OUT stats_since timestamp with time zone, OUT minmax_stats_since timestamp with time zone) FROM postgres;
GRANT ALL ON FUNCTION extensions.pg_stat_statements(showtext boolean, OUT userid oid, OUT dbid oid, OUT toplevel boolean, OUT queryid bigint, OUT query text, OUT plans bigint, OUT total_plan_time double precision, OUT min_plan_time double precision, OUT max_plan_time double precision, OUT mean_plan_time double precision, OUT stddev_plan_time double precision, OUT calls bigint, OUT total_exec_time double precision, OUT min_exec_time double precision, OUT max_exec_time double precision, OUT mean_exec_time double precision, OUT stddev_exec_time double precision, OUT rows bigint, OUT shared_blks_hit bigint, OUT shared_blks_read bigint, OUT shared_blks_dirtied bigint, OUT shared_blks_written bigint, OUT local_blks_hit bigint, OUT local_blks_read bigint, OUT local_blks_dirtied bigint, OUT local_blks_written bigint, OUT temp_blks_read bigint, OUT temp_blks_written bigint, OUT shared_blk_read_time double precision, OUT shared_blk_write_time double precision, OUT local_blk_read_time double precision, OUT local_blk_write_time double precision, OUT temp_blk_read_time double precision, OUT temp_blk_write_time double precision, OUT wal_records bigint, OUT wal_fpi bigint, OUT wal_bytes numeric, OUT jit_functions bigint, OUT jit_generation_time double precision, OUT jit_inlining_count bigint, OUT jit_inlining_time double precision, OUT jit_optimization_count bigint, OUT jit_optimization_time double precision, OUT jit_emission_count bigint, OUT jit_emission_time double precision, OUT jit_deform_count bigint, OUT jit_deform_time double precision, OUT stats_since timestamp with time zone, OUT minmax_stats_since timestamp with time zone) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pg_stat_statements(showtext boolean, OUT userid oid, OUT dbid oid, OUT toplevel boolean, OUT queryid bigint, OUT query text, OUT plans bigint, OUT total_plan_time double precision, OUT min_plan_time double precision, OUT max_plan_time double precision, OUT mean_plan_time double precision, OUT stddev_plan_time double precision, OUT calls bigint, OUT total_exec_time double precision, OUT min_exec_time double precision, OUT max_exec_time double precision, OUT mean_exec_time double precision, OUT stddev_exec_time double precision, OUT rows bigint, OUT shared_blks_hit bigint, OUT shared_blks_read bigint, OUT shared_blks_dirtied bigint, OUT shared_blks_written bigint, OUT local_blks_hit bigint, OUT local_blks_read bigint, OUT local_blks_dirtied bigint, OUT local_blks_written bigint, OUT temp_blks_read bigint, OUT temp_blks_written bigint, OUT shared_blk_read_time double precision, OUT shared_blk_write_time double precision, OUT local_blk_read_time double precision, OUT local_blk_write_time double precision, OUT temp_blk_read_time double precision, OUT temp_blk_write_time double precision, OUT wal_records bigint, OUT wal_fpi bigint, OUT wal_bytes numeric, OUT jit_functions bigint, OUT jit_generation_time double precision, OUT jit_inlining_count bigint, OUT jit_inlining_time double precision, OUT jit_optimization_count bigint, OUT jit_optimization_time double precision, OUT jit_emission_count bigint, OUT jit_emission_time double precision, OUT jit_deform_count bigint, OUT jit_deform_time double precision, OUT stats_since timestamp with time zone, OUT minmax_stats_since timestamp with time zone) TO dashboard_user;


--
-- Name: FUNCTION pg_stat_statements_info(OUT dealloc bigint, OUT stats_reset timestamp with time zone); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pg_stat_statements_info(OUT dealloc bigint, OUT stats_reset timestamp with time zone) FROM postgres;
GRANT ALL ON FUNCTION extensions.pg_stat_statements_info(OUT dealloc bigint, OUT stats_reset timestamp with time zone) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pg_stat_statements_info(OUT dealloc bigint, OUT stats_reset timestamp with time zone) TO dashboard_user;


--
-- Name: FUNCTION pg_stat_statements_reset(userid oid, dbid oid, queryid bigint, minmax_only boolean); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pg_stat_statements_reset(userid oid, dbid oid, queryid bigint, minmax_only boolean) FROM postgres;
GRANT ALL ON FUNCTION extensions.pg_stat_statements_reset(userid oid, dbid oid, queryid bigint, minmax_only boolean) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pg_stat_statements_reset(userid oid, dbid oid, queryid bigint, minmax_only boolean) TO dashboard_user;


--
-- Name: FUNCTION pgp_armor_headers(text, OUT key text, OUT value text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_armor_headers(text, OUT key text, OUT value text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_armor_headers(text, OUT key text, OUT value text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_armor_headers(text, OUT key text, OUT value text) TO dashboard_user;


--
-- Name: FUNCTION pgp_key_id(bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_key_id(bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_key_id(bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_key_id(bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt(bytea, bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt(bytea, bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt(bytea, bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt_bytea(bytea, bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt_bytea(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_decrypt_bytea(bytea, bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_decrypt_bytea(bytea, bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_encrypt(text, bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_encrypt(text, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt(text, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_encrypt_bytea(bytea, bytea); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea) TO dashboard_user;


--
-- Name: FUNCTION pgp_pub_encrypt_bytea(bytea, bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_pub_encrypt_bytea(bytea, bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_decrypt(bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_decrypt(bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt(bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_decrypt_bytea(bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_decrypt_bytea(bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_decrypt_bytea(bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_encrypt(text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_encrypt(text, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt(text, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_encrypt_bytea(bytea, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text) TO dashboard_user;


--
-- Name: FUNCTION pgp_sym_encrypt_bytea(bytea, text, text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text, text) FROM postgres;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text, text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.pgp_sym_encrypt_bytea(bytea, text, text) TO dashboard_user;


--
-- Name: FUNCTION pgrst_ddl_watch(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

GRANT ALL ON FUNCTION extensions.pgrst_ddl_watch() TO postgres WITH GRANT OPTION;


--
-- Name: FUNCTION pgrst_drop_watch(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

GRANT ALL ON FUNCTION extensions.pgrst_drop_watch() TO postgres WITH GRANT OPTION;


--
-- Name: FUNCTION set_graphql_placeholder(); Type: ACL; Schema: extensions; Owner: supabase_admin
--

GRANT ALL ON FUNCTION extensions.set_graphql_placeholder() TO postgres WITH GRANT OPTION;


--
-- Name: FUNCTION uuid_generate_v1(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v1() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v1() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v1() TO dashboard_user;


--
-- Name: FUNCTION uuid_generate_v1mc(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v1mc() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v1mc() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v1mc() TO dashboard_user;


--
-- Name: FUNCTION uuid_generate_v3(namespace uuid, name text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v3(namespace uuid, name text) FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v3(namespace uuid, name text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v3(namespace uuid, name text) TO dashboard_user;


--
-- Name: FUNCTION uuid_generate_v4(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v4() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v4() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v4() TO dashboard_user;


--
-- Name: FUNCTION uuid_generate_v5(namespace uuid, name text); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_generate_v5(namespace uuid, name text) FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_generate_v5(namespace uuid, name text) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_generate_v5(namespace uuid, name text) TO dashboard_user;


--
-- Name: FUNCTION uuid_nil(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_nil() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_nil() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_nil() TO dashboard_user;


--
-- Name: FUNCTION uuid_ns_dns(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_ns_dns() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_ns_dns() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_ns_dns() TO dashboard_user;


--
-- Name: FUNCTION uuid_ns_oid(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_ns_oid() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_ns_oid() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_ns_oid() TO dashboard_user;


--
-- Name: FUNCTION uuid_ns_url(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_ns_url() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_ns_url() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_ns_url() TO dashboard_user;


--
-- Name: FUNCTION uuid_ns_x500(); Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON FUNCTION extensions.uuid_ns_x500() FROM postgres;
GRANT ALL ON FUNCTION extensions.uuid_ns_x500() TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION extensions.uuid_ns_x500() TO dashboard_user;


--
-- Name: FUNCTION graphql("operationName" text, query text, variables jsonb, extensions jsonb); Type: ACL; Schema: graphql_public; Owner: supabase_admin
--

GRANT ALL ON FUNCTION graphql_public.graphql("operationName" text, query text, variables jsonb, extensions jsonb) TO postgres;
GRANT ALL ON FUNCTION graphql_public.graphql("operationName" text, query text, variables jsonb, extensions jsonb) TO anon;
GRANT ALL ON FUNCTION graphql_public.graphql("operationName" text, query text, variables jsonb, extensions jsonb) TO authenticated;
GRANT ALL ON FUNCTION graphql_public.graphql("operationName" text, query text, variables jsonb, extensions jsonb) TO service_role;


--
-- Name: FUNCTION pg_reload_conf(); Type: ACL; Schema: pg_catalog; Owner: supabase_admin
--

GRANT ALL ON FUNCTION pg_catalog.pg_reload_conf() TO postgres WITH GRANT OPTION;


--
-- Name: FUNCTION get_auth(p_usename text); Type: ACL; Schema: pgbouncer; Owner: supabase_admin
--

REVOKE ALL ON FUNCTION pgbouncer.get_auth(p_usename text) FROM PUBLIC;
GRANT ALL ON FUNCTION pgbouncer.get_auth(p_usename text) TO pgbouncer;


--
-- Name: FUNCTION fn_calcular_precio_tienda(); Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON FUNCTION public.fn_calcular_precio_tienda() TO anon;
GRANT ALL ON FUNCTION public.fn_calcular_precio_tienda() TO authenticated;
GRANT ALL ON FUNCTION public.fn_calcular_precio_tienda() TO service_role;


--
-- Name: FUNCTION fn_cerrar_precio_anterior(); Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON FUNCTION public.fn_cerrar_precio_anterior() TO anon;
GRANT ALL ON FUNCTION public.fn_cerrar_precio_anterior() TO authenticated;
GRANT ALL ON FUNCTION public.fn_cerrar_precio_anterior() TO service_role;


--
-- Name: FUNCTION rls_auto_enable(); Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON FUNCTION public.rls_auto_enable() TO anon;
GRANT ALL ON FUNCTION public.rls_auto_enable() TO authenticated;
GRANT ALL ON FUNCTION public.rls_auto_enable() TO service_role;


--
-- Name: FUNCTION simular_precio(p_id_producto bigint, p_id_margen bigint, p_costo numeric); Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON FUNCTION public.simular_precio(p_id_producto bigint, p_id_margen bigint, p_costo numeric) TO anon;
GRANT ALL ON FUNCTION public.simular_precio(p_id_producto bigint, p_id_margen bigint, p_costo numeric) TO authenticated;
GRANT ALL ON FUNCTION public.simular_precio(p_id_producto bigint, p_id_margen bigint, p_costo numeric) TO service_role;


--
-- Name: FUNCTION apply_rls(wal jsonb, max_record_bytes integer); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO postgres;
GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO anon;
GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO authenticated;
GRANT ALL ON FUNCTION realtime.apply_rls(wal jsonb, max_record_bytes integer) TO service_role;


--
-- Name: FUNCTION broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text) TO postgres;
GRANT ALL ON FUNCTION realtime.broadcast_changes(topic_name text, event_name text, operation text, table_name text, table_schema text, new record, old record, level text) TO dashboard_user;


--
-- Name: FUNCTION build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO postgres;
GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO anon;
GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO authenticated;
GRANT ALL ON FUNCTION realtime.build_prepared_statement_sql(prepared_statement_name text, entity regclass, columns realtime.wal_column[]) TO service_role;


--
-- Name: FUNCTION "cast"(val text, type_ regtype); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO postgres;
GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO dashboard_user;
GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO anon;
GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO authenticated;
GRANT ALL ON FUNCTION realtime."cast"(val text, type_ regtype) TO service_role;


--
-- Name: FUNCTION check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO postgres;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO anon;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO authenticated;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text) TO service_role;


--
-- Name: FUNCTION check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text, negate boolean); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text, negate boolean) TO postgres;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text, negate boolean) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text, negate boolean) TO anon;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text, negate boolean) TO authenticated;
GRANT ALL ON FUNCTION realtime.check_equality_op(op realtime.equality_op, type_ regtype, val_1 text, val_2 text, negate boolean) TO service_role;


--
-- Name: FUNCTION is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO postgres;
GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO anon;
GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO authenticated;
GRANT ALL ON FUNCTION realtime.is_visible_through_filters(columns realtime.wal_column[], filters realtime.user_defined_filter[]) TO service_role;


--
-- Name: FUNCTION list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO postgres;
GRANT ALL ON FUNCTION realtime.list_changes(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO dashboard_user;


--
-- Name: FUNCTION list_changes_sync(publication name, slot_name name, max_changes integer, max_record_bytes integer); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.list_changes_sync(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO postgres;
GRANT ALL ON FUNCTION realtime.list_changes_sync(publication name, slot_name name, max_changes integer, max_record_bytes integer) TO dashboard_user;


--
-- Name: FUNCTION quote_wal2json(entity regclass); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO postgres;
GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO anon;
GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO authenticated;
GRANT ALL ON FUNCTION realtime.quote_wal2json(entity regclass) TO service_role;


--
-- Name: FUNCTION send(payload jsonb, event text, topic text, private boolean); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.send(payload jsonb, event text, topic text, private boolean) TO postgres;
GRANT ALL ON FUNCTION realtime.send(payload jsonb, event text, topic text, private boolean) TO dashboard_user;


--
-- Name: FUNCTION send_binary(payload bytea, event text, topic text, private boolean); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.send_binary(payload bytea, event text, topic text, private boolean) TO postgres;
GRANT ALL ON FUNCTION realtime.send_binary(payload bytea, event text, topic text, private boolean) TO dashboard_user;


--
-- Name: FUNCTION settled_changes(slot_name name, max_changes integer, VARIADIC opts text[]); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.settled_changes(slot_name name, max_changes integer, VARIADIC opts text[]) TO postgres;
GRANT ALL ON FUNCTION realtime.settled_changes(slot_name name, max_changes integer, VARIADIC opts text[]) TO dashboard_user;


--
-- Name: FUNCTION subscription_check_filters(); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO postgres;
GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO dashboard_user;
GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO anon;
GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO authenticated;
GRANT ALL ON FUNCTION realtime.subscription_check_filters() TO service_role;


--
-- Name: FUNCTION to_regrole(role_name text); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO postgres;
GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO dashboard_user;
GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO anon;
GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO authenticated;
GRANT ALL ON FUNCTION realtime.to_regrole(role_name text) TO service_role;


--
-- Name: FUNCTION topic(); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.topic() TO postgres;
GRANT ALL ON FUNCTION realtime.topic() TO dashboard_user;


--
-- Name: FUNCTION wal2json_escape_identifier(name text); Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON FUNCTION realtime.wal2json_escape_identifier(name text) TO postgres;
GRANT ALL ON FUNCTION realtime.wal2json_escape_identifier(name text) TO dashboard_user;


--
-- Name: FUNCTION _crypto_aead_det_decrypt(message bytea, additional bytea, key_id bigint, context bytea, nonce bytea); Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT ALL ON FUNCTION vault._crypto_aead_det_decrypt(message bytea, additional bytea, key_id bigint, context bytea, nonce bytea) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION vault._crypto_aead_det_decrypt(message bytea, additional bytea, key_id bigint, context bytea, nonce bytea) TO service_role;


--
-- Name: FUNCTION create_secret(new_secret text, new_name text, new_description text, new_key_id uuid); Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT ALL ON FUNCTION vault.create_secret(new_secret text, new_name text, new_description text, new_key_id uuid) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION vault.create_secret(new_secret text, new_name text, new_description text, new_key_id uuid) TO service_role;


--
-- Name: FUNCTION update_secret(secret_id uuid, new_secret text, new_name text, new_description text, new_key_id uuid); Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT ALL ON FUNCTION vault.update_secret(secret_id uuid, new_secret text, new_name text, new_description text, new_key_id uuid) TO postgres WITH GRANT OPTION;
GRANT ALL ON FUNCTION vault.update_secret(secret_id uuid, new_secret text, new_name text, new_description text, new_key_id uuid) TO service_role;


--
-- Name: TABLE audit_log_entries; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.audit_log_entries TO dashboard_user;
GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.audit_log_entries TO postgres;
GRANT SELECT ON TABLE auth.audit_log_entries TO postgres WITH GRANT OPTION;


--
-- Name: TABLE custom_oauth_providers; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.custom_oauth_providers TO postgres;
GRANT ALL ON TABLE auth.custom_oauth_providers TO dashboard_user;


--
-- Name: TABLE flow_state; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.flow_state TO postgres;
GRANT SELECT ON TABLE auth.flow_state TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.flow_state TO dashboard_user;


--
-- Name: TABLE identities; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.identities TO postgres;
GRANT SELECT ON TABLE auth.identities TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.identities TO dashboard_user;


--
-- Name: TABLE instances; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.instances TO dashboard_user;
GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.instances TO postgres;
GRANT SELECT ON TABLE auth.instances TO postgres WITH GRANT OPTION;


--
-- Name: TABLE mfa_amr_claims; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.mfa_amr_claims TO postgres;
GRANT SELECT ON TABLE auth.mfa_amr_claims TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.mfa_amr_claims TO dashboard_user;


--
-- Name: TABLE mfa_challenges; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.mfa_challenges TO postgres;
GRANT SELECT ON TABLE auth.mfa_challenges TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.mfa_challenges TO dashboard_user;


--
-- Name: TABLE mfa_factors; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.mfa_factors TO postgres;
GRANT SELECT ON TABLE auth.mfa_factors TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.mfa_factors TO dashboard_user;


--
-- Name: TABLE mfa_recovery_code_sets; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.mfa_recovery_code_sets TO postgres;
GRANT ALL ON TABLE auth.mfa_recovery_code_sets TO dashboard_user;


--
-- Name: TABLE mfa_recovery_codes; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.mfa_recovery_codes TO postgres;
GRANT ALL ON TABLE auth.mfa_recovery_codes TO dashboard_user;


--
-- Name: TABLE oauth_authorizations; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.oauth_authorizations TO postgres;
GRANT ALL ON TABLE auth.oauth_authorizations TO dashboard_user;


--
-- Name: TABLE oauth_client_states; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.oauth_client_states TO postgres;
GRANT ALL ON TABLE auth.oauth_client_states TO dashboard_user;


--
-- Name: TABLE oauth_clients; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.oauth_clients TO postgres;
GRANT ALL ON TABLE auth.oauth_clients TO dashboard_user;


--
-- Name: TABLE oauth_consents; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.oauth_consents TO postgres;
GRANT ALL ON TABLE auth.oauth_consents TO dashboard_user;


--
-- Name: TABLE one_time_tokens; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.one_time_tokens TO postgres;
GRANT SELECT ON TABLE auth.one_time_tokens TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.one_time_tokens TO dashboard_user;


--
-- Name: TABLE refresh_tokens; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.refresh_tokens TO dashboard_user;
GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.refresh_tokens TO postgres;
GRANT SELECT ON TABLE auth.refresh_tokens TO postgres WITH GRANT OPTION;


--
-- Name: SEQUENCE refresh_tokens_id_seq; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON SEQUENCE auth.refresh_tokens_id_seq TO dashboard_user;
GRANT ALL ON SEQUENCE auth.refresh_tokens_id_seq TO postgres;


--
-- Name: TABLE saml_providers; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.saml_providers TO postgres;
GRANT SELECT ON TABLE auth.saml_providers TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.saml_providers TO dashboard_user;


--
-- Name: TABLE saml_relay_states; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.saml_relay_states TO postgres;
GRANT SELECT ON TABLE auth.saml_relay_states TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.saml_relay_states TO dashboard_user;


--
-- Name: TABLE schema_migrations; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT SELECT ON TABLE auth.schema_migrations TO postgres WITH GRANT OPTION;


--
-- Name: TABLE scim_tokens; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.scim_tokens TO postgres;
GRANT ALL ON TABLE auth.scim_tokens TO dashboard_user;


--
-- Name: TABLE scim_users; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.scim_users TO postgres;
GRANT ALL ON TABLE auth.scim_users TO dashboard_user;


--
-- Name: TABLE sessions; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.sessions TO postgres;
GRANT SELECT ON TABLE auth.sessions TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.sessions TO dashboard_user;


--
-- Name: TABLE sso_domains; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.sso_domains TO postgres;
GRANT SELECT ON TABLE auth.sso_domains TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.sso_domains TO dashboard_user;


--
-- Name: TABLE sso_providers; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.sso_providers TO postgres;
GRANT SELECT ON TABLE auth.sso_providers TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE auth.sso_providers TO dashboard_user;


--
-- Name: TABLE users; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.users TO dashboard_user;
GRANT INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE auth.users TO postgres;
GRANT SELECT ON TABLE auth.users TO postgres WITH GRANT OPTION;


--
-- Name: TABLE webauthn_challenges; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.webauthn_challenges TO postgres;
GRANT ALL ON TABLE auth.webauthn_challenges TO dashboard_user;


--
-- Name: TABLE webauthn_credentials; Type: ACL; Schema: auth; Owner: supabase_auth_admin
--

GRANT ALL ON TABLE auth.webauthn_credentials TO postgres;
GRANT ALL ON TABLE auth.webauthn_credentials TO dashboard_user;


--
-- Name: TABLE pg_stat_statements; Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON TABLE extensions.pg_stat_statements FROM postgres;
GRANT ALL ON TABLE extensions.pg_stat_statements TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE extensions.pg_stat_statements TO dashboard_user;


--
-- Name: TABLE pg_stat_statements_info; Type: ACL; Schema: extensions; Owner: postgres
--

REVOKE ALL ON TABLE extensions.pg_stat_statements_info FROM postgres;
GRANT ALL ON TABLE extensions.pg_stat_statements_info TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE extensions.pg_stat_statements_info TO dashboard_user;


--
-- Name: TABLE bitacora_auditoria; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.bitacora_auditoria TO anon;
GRANT ALL ON TABLE public.bitacora_auditoria TO authenticated;
GRANT ALL ON TABLE public.bitacora_auditoria TO service_role;


--
-- Name: SEQUENCE bitacora_auditoria_id_evento_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.bitacora_auditoria_id_evento_seq TO anon;
GRANT ALL ON SEQUENCE public.bitacora_auditoria_id_evento_seq TO authenticated;
GRANT ALL ON SEQUENCE public.bitacora_auditoria_id_evento_seq TO service_role;


--
-- Name: TABLE categoria; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.categoria TO anon;
GRANT ALL ON TABLE public.categoria TO authenticated;
GRANT ALL ON TABLE public.categoria TO service_role;


--
-- Name: SEQUENCE categoria_id_categoria_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.categoria_id_categoria_seq TO anon;
GRANT ALL ON SEQUENCE public.categoria_id_categoria_seq TO authenticated;
GRANT ALL ON SEQUENCE public.categoria_id_categoria_seq TO service_role;


--
-- Name: TABLE impuesto; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.impuesto TO anon;
GRANT ALL ON TABLE public.impuesto TO authenticated;
GRANT ALL ON TABLE public.impuesto TO service_role;


--
-- Name: SEQUENCE impuesto_id_impuesto_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.impuesto_id_impuesto_seq TO anon;
GRANT ALL ON SEQUENCE public.impuesto_id_impuesto_seq TO authenticated;
GRANT ALL ON SEQUENCE public.impuesto_id_impuesto_seq TO service_role;


--
-- Name: TABLE maquina; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.maquina TO anon;
GRANT ALL ON TABLE public.maquina TO authenticated;
GRANT ALL ON TABLE public.maquina TO service_role;


--
-- Name: SEQUENCE maquina_id_maquina_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.maquina_id_maquina_seq TO anon;
GRANT ALL ON SEQUENCE public.maquina_id_maquina_seq TO authenticated;
GRANT ALL ON SEQUENCE public.maquina_id_maquina_seq TO service_role;


--
-- Name: TABLE maquina_producto; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.maquina_producto TO anon;
GRANT ALL ON TABLE public.maquina_producto TO authenticated;
GRANT ALL ON TABLE public.maquina_producto TO service_role;


--
-- Name: SEQUENCE maquina_producto_id_maquina_producto_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.maquina_producto_id_maquina_producto_seq TO anon;
GRANT ALL ON SEQUENCE public.maquina_producto_id_maquina_producto_seq TO authenticated;
GRANT ALL ON SEQUENCE public.maquina_producto_id_maquina_producto_seq TO service_role;


--
-- Name: TABLE margen_ganancia; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.margen_ganancia TO anon;
GRANT ALL ON TABLE public.margen_ganancia TO authenticated;
GRANT ALL ON TABLE public.margen_ganancia TO service_role;


--
-- Name: SEQUENCE margen_ganancia_id_margen_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.margen_ganancia_id_margen_seq TO anon;
GRANT ALL ON SEQUENCE public.margen_ganancia_id_margen_seq TO authenticated;
GRANT ALL ON SEQUENCE public.margen_ganancia_id_margen_seq TO service_role;


--
-- Name: TABLE precio_tienda; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.precio_tienda TO anon;
GRANT ALL ON TABLE public.precio_tienda TO authenticated;
GRANT ALL ON TABLE public.precio_tienda TO service_role;


--
-- Name: SEQUENCE precio_tienda_id_precio_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.precio_tienda_id_precio_seq TO anon;
GRANT ALL ON SEQUENCE public.precio_tienda_id_precio_seq TO authenticated;
GRANT ALL ON SEQUENCE public.precio_tienda_id_precio_seq TO service_role;


--
-- Name: TABLE producto; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.producto TO anon;
GRANT ALL ON TABLE public.producto TO authenticated;
GRANT ALL ON TABLE public.producto TO service_role;


--
-- Name: SEQUENCE producto_id_producto_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.producto_id_producto_seq TO anon;
GRANT ALL ON SEQUENCE public.producto_id_producto_seq TO authenticated;
GRANT ALL ON SEQUENCE public.producto_id_producto_seq TO service_role;


--
-- Name: TABLE producto_proveedor; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.producto_proveedor TO anon;
GRANT ALL ON TABLE public.producto_proveedor TO authenticated;
GRANT ALL ON TABLE public.producto_proveedor TO service_role;


--
-- Name: SEQUENCE producto_proveedor_id_producto_proveedor_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.producto_proveedor_id_producto_proveedor_seq TO anon;
GRANT ALL ON SEQUENCE public.producto_proveedor_id_producto_proveedor_seq TO authenticated;
GRANT ALL ON SEQUENCE public.producto_proveedor_id_producto_proveedor_seq TO service_role;


--
-- Name: TABLE proveedor; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.proveedor TO anon;
GRANT ALL ON TABLE public.proveedor TO authenticated;
GRANT ALL ON TABLE public.proveedor TO service_role;


--
-- Name: SEQUENCE proveedor_id_proveedor_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.proveedor_id_proveedor_seq TO anon;
GRANT ALL ON SEQUENCE public.proveedor_id_proveedor_seq TO authenticated;
GRANT ALL ON SEQUENCE public.proveedor_id_proveedor_seq TO service_role;


--
-- Name: TABLE recuperacion_contrasena; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.recuperacion_contrasena TO anon;
GRANT ALL ON TABLE public.recuperacion_contrasena TO authenticated;
GRANT ALL ON TABLE public.recuperacion_contrasena TO service_role;


--
-- Name: SEQUENCE recuperacion_contrasena_id_recuperacion_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.recuperacion_contrasena_id_recuperacion_seq TO anon;
GRANT ALL ON SEQUENCE public.recuperacion_contrasena_id_recuperacion_seq TO authenticated;
GRANT ALL ON SEQUENCE public.recuperacion_contrasena_id_recuperacion_seq TO service_role;


--
-- Name: TABLE relacion_abastecimiento; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.relacion_abastecimiento TO anon;
GRANT ALL ON TABLE public.relacion_abastecimiento TO authenticated;
GRANT ALL ON TABLE public.relacion_abastecimiento TO service_role;


--
-- Name: SEQUENCE relacion_abastecimiento_id_relacion_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.relacion_abastecimiento_id_relacion_seq TO anon;
GRANT ALL ON SEQUENCE public.relacion_abastecimiento_id_relacion_seq TO authenticated;
GRANT ALL ON SEQUENCE public.relacion_abastecimiento_id_relacion_seq TO service_role;


--
-- Name: TABLE respaldo; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.respaldo TO anon;
GRANT ALL ON TABLE public.respaldo TO authenticated;
GRANT ALL ON TABLE public.respaldo TO service_role;


--
-- Name: SEQUENCE respaldo_id_respaldo_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.respaldo_id_respaldo_seq TO anon;
GRANT ALL ON SEQUENCE public.respaldo_id_respaldo_seq TO authenticated;
GRANT ALL ON SEQUENCE public.respaldo_id_respaldo_seq TO service_role;


--
-- Name: TABLE rol; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.rol TO anon;
GRANT ALL ON TABLE public.rol TO authenticated;
GRANT ALL ON TABLE public.rol TO service_role;


--
-- Name: SEQUENCE rol_id_rol_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.rol_id_rol_seq TO anon;
GRANT ALL ON SEQUENCE public.rol_id_rol_seq TO authenticated;
GRANT ALL ON SEQUENCE public.rol_id_rol_seq TO service_role;


--
-- Name: TABLE tienda; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.tienda TO anon;
GRANT ALL ON TABLE public.tienda TO authenticated;
GRANT ALL ON TABLE public.tienda TO service_role;


--
-- Name: SEQUENCE tienda_id_tienda_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.tienda_id_tienda_seq TO anon;
GRANT ALL ON SEQUENCE public.tienda_id_tienda_seq TO authenticated;
GRANT ALL ON SEQUENCE public.tienda_id_tienda_seq TO service_role;


--
-- Name: TABLE usuario; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.usuario TO anon;
GRANT ALL ON TABLE public.usuario TO authenticated;
GRANT ALL ON TABLE public.usuario TO service_role;


--
-- Name: SEQUENCE usuario_id_usuario_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.usuario_id_usuario_seq TO anon;
GRANT ALL ON SEQUENCE public.usuario_id_usuario_seq TO authenticated;
GRANT ALL ON SEQUENCE public.usuario_id_usuario_seq TO service_role;


--
-- Name: TABLE usuario_tienda; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.usuario_tienda TO anon;
GRANT ALL ON TABLE public.usuario_tienda TO authenticated;
GRANT ALL ON TABLE public.usuario_tienda TO service_role;


--
-- Name: SEQUENCE usuario_tienda_id_usuario_tienda_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.usuario_tienda_id_usuario_tienda_seq TO anon;
GRANT ALL ON SEQUENCE public.usuario_tienda_id_usuario_tienda_seq TO authenticated;
GRANT ALL ON SEQUENCE public.usuario_tienda_id_usuario_tienda_seq TO service_role;


--
-- Name: TABLE messages; Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLE realtime.messages TO postgres;
GRANT SELECT,INSERT ON TABLE realtime.messages TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE realtime.messages TO dashboard_user;
GRANT SELECT,INSERT,UPDATE ON TABLE realtime.messages TO anon;
GRANT SELECT,INSERT,UPDATE ON TABLE realtime.messages TO authenticated;
GRANT SELECT,INSERT,UPDATE ON TABLE realtime.messages TO service_role;


--
-- Name: TABLE schema_migrations; Type: ACL; Schema: realtime; Owner: supabase_admin
--

GRANT ALL ON TABLE realtime.schema_migrations TO postgres;
GRANT ALL ON TABLE realtime.schema_migrations TO dashboard_user;


--
-- Name: TABLE subscription; Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON TABLE realtime.subscription TO postgres;
GRANT ALL ON TABLE realtime.subscription TO dashboard_user;
GRANT SELECT ON TABLE realtime.subscription TO anon;
GRANT SELECT ON TABLE realtime.subscription TO authenticated;
GRANT SELECT ON TABLE realtime.subscription TO service_role;


--
-- Name: SEQUENCE subscription_id_seq; Type: ACL; Schema: realtime; Owner: supabase_realtime_admin
--

GRANT ALL ON SEQUENCE realtime.subscription_id_seq TO postgres;
GRANT ALL ON SEQUENCE realtime.subscription_id_seq TO dashboard_user;
GRANT USAGE ON SEQUENCE realtime.subscription_id_seq TO anon;
GRANT USAGE ON SEQUENCE realtime.subscription_id_seq TO authenticated;
GRANT USAGE ON SEQUENCE realtime.subscription_id_seq TO service_role;


--
-- Name: TABLE buckets; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

REVOKE ALL ON TABLE storage.buckets FROM supabase_storage_admin;
GRANT ALL ON TABLE storage.buckets TO supabase_storage_admin WITH GRANT OPTION;
GRANT ALL ON TABLE storage.buckets TO service_role;
GRANT ALL ON TABLE storage.buckets TO authenticated;
GRANT ALL ON TABLE storage.buckets TO anon;
GRANT ALL ON TABLE storage.buckets TO postgres WITH GRANT OPTION;


--
-- Name: TABLE buckets_analytics; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.buckets_analytics TO service_role;
GRANT ALL ON TABLE storage.buckets_analytics TO authenticated;
GRANT ALL ON TABLE storage.buckets_analytics TO anon;


--
-- Name: TABLE buckets_vectors; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT SELECT ON TABLE storage.buckets_vectors TO service_role;
GRANT SELECT ON TABLE storage.buckets_vectors TO authenticated;
GRANT SELECT ON TABLE storage.buckets_vectors TO anon;


--
-- Name: TABLE objects; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

REVOKE ALL ON TABLE storage.objects FROM supabase_storage_admin;
GRANT ALL ON TABLE storage.objects TO supabase_storage_admin WITH GRANT OPTION;
GRANT ALL ON TABLE storage.objects TO service_role;
GRANT ALL ON TABLE storage.objects TO authenticated;
GRANT ALL ON TABLE storage.objects TO anon;
GRANT ALL ON TABLE storage.objects TO postgres WITH GRANT OPTION;


--
-- Name: TABLE s3_multipart_uploads; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.s3_multipart_uploads TO service_role;
GRANT SELECT ON TABLE storage.s3_multipart_uploads TO authenticated;
GRANT SELECT ON TABLE storage.s3_multipart_uploads TO anon;


--
-- Name: TABLE s3_multipart_uploads_parts; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT ALL ON TABLE storage.s3_multipart_uploads_parts TO service_role;
GRANT SELECT ON TABLE storage.s3_multipart_uploads_parts TO authenticated;
GRANT SELECT ON TABLE storage.s3_multipart_uploads_parts TO anon;


--
-- Name: TABLE vector_indexes; Type: ACL; Schema: storage; Owner: supabase_storage_admin
--

GRANT SELECT ON TABLE storage.vector_indexes TO service_role;
GRANT SELECT ON TABLE storage.vector_indexes TO authenticated;
GRANT SELECT ON TABLE storage.vector_indexes TO anon;


--
-- Name: TABLE secrets; Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT SELECT,REFERENCES,DELETE,TRUNCATE ON TABLE vault.secrets TO postgres WITH GRANT OPTION;
GRANT SELECT,DELETE ON TABLE vault.secrets TO service_role;


--
-- Name: TABLE decrypted_secrets; Type: ACL; Schema: vault; Owner: supabase_admin
--

GRANT SELECT,REFERENCES,DELETE,TRUNCATE ON TABLE vault.decrypted_secrets TO postgres WITH GRANT OPTION;
GRANT SELECT,DELETE ON TABLE vault.decrypted_secrets TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: auth; Owner: supabase_auth_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON SEQUENCES TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: auth; Owner: supabase_auth_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON FUNCTIONS TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: auth; Owner: supabase_auth_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_auth_admin IN SCHEMA auth GRANT ALL ON TABLES TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: extensions; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA extensions GRANT ALL ON SEQUENCES TO postgres WITH GRANT OPTION;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: extensions; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA extensions GRANT ALL ON FUNCTIONS TO postgres WITH GRANT OPTION;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: extensions; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA extensions GRANT ALL ON TABLES TO postgres WITH GRANT OPTION;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: graphql; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: graphql; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: graphql; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql GRANT ALL ON TABLES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: graphql_public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: graphql_public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: graphql_public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA graphql_public GRANT ALL ON TABLES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: public; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA public GRANT ALL ON TABLES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: realtime; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON SEQUENCES TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: realtime; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON FUNCTIONS TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: realtime; Owner: supabase_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT REFERENCES,DELETE,TRIGGER,TRUNCATE,MAINTAIN,UPDATE ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT SELECT,INSERT ON TABLES TO postgres WITH GRANT OPTION;
ALTER DEFAULT PRIVILEGES FOR ROLE supabase_admin IN SCHEMA realtime GRANT ALL ON TABLES TO dashboard_user;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: storage; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON SEQUENCES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON SEQUENCES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON SEQUENCES TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: storage; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON FUNCTIONS TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON FUNCTIONS TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON FUNCTIONS TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON FUNCTIONS TO service_role;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: storage; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON TABLES TO postgres;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON TABLES TO authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA storage GRANT ALL ON TABLES TO service_role;


--
-- Name: ensure_rls; Type: EVENT TRIGGER; Schema: -; Owner: postgres
--

CREATE EVENT TRIGGER ensure_rls ON ddl_command_end
         WHEN TAG IN ('CREATE TABLE', 'CREATE TABLE AS', 'SELECT INTO')
   EXECUTE FUNCTION public.rls_auto_enable();


ALTER EVENT TRIGGER ensure_rls OWNER TO postgres;

--
-- Name: issue_graphql_placeholder; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER issue_graphql_placeholder ON sql_drop
         WHEN TAG IN ('DROP EXTENSION')
   EXECUTE FUNCTION extensions.set_graphql_placeholder();


ALTER EVENT TRIGGER issue_graphql_placeholder OWNER TO supabase_admin;

--
-- Name: issue_pg_cron_access; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER issue_pg_cron_access ON ddl_command_end
         WHEN TAG IN ('CREATE EXTENSION')
   EXECUTE FUNCTION extensions.grant_pg_cron_access();


ALTER EVENT TRIGGER issue_pg_cron_access OWNER TO supabase_admin;

--
-- Name: issue_pg_graphql_access; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER issue_pg_graphql_access ON ddl_command_end
         WHEN TAG IN ('CREATE EXTENSION')
   EXECUTE FUNCTION extensions.grant_pg_graphql_access();


ALTER EVENT TRIGGER issue_pg_graphql_access OWNER TO supabase_admin;

--
-- Name: issue_pg_net_access; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER issue_pg_net_access ON ddl_command_end
         WHEN TAG IN ('CREATE EXTENSION')
   EXECUTE FUNCTION extensions.grant_pg_net_access();


ALTER EVENT TRIGGER issue_pg_net_access OWNER TO supabase_admin;

--
-- Name: pgrst_ddl_watch; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER pgrst_ddl_watch ON ddl_command_end
   EXECUTE FUNCTION extensions.pgrst_ddl_watch();


ALTER EVENT TRIGGER pgrst_ddl_watch OWNER TO supabase_admin;

--
-- Name: pgrst_drop_watch; Type: EVENT TRIGGER; Schema: -; Owner: supabase_admin
--

CREATE EVENT TRIGGER pgrst_drop_watch ON sql_drop
   EXECUTE FUNCTION extensions.pgrst_drop_watch();


ALTER EVENT TRIGGER pgrst_drop_watch OWNER TO supabase_admin;

--
-- PostgreSQL database dump complete
--

\unrestrict 9T5maxz3iQ2k0nksZ57a88RJ2HkzThbGofgcozC8GOVZq7KaNIlZdQCrT5haQEB

