/**
 * SQLite
 */

DROP TABLE IF EXISTS "post";
CREATE TABLE "post" (
  "id"    INTEGER NOT NULL PRIMARY KEY,
  "title" TEXT    NOT NULL,
  "body"  TEXT    NOT NULL
);

DROP TABLE IF EXISTS "tag";
CREATE TABLE "tag" (
  "id"        INTEGER NOT NULL PRIMARY KEY,
  "slug"      VARCHAR (6)   NOT NULL,
  "name"      VARCHAR (6)   NOT NULL,
  "frequency" INTEGER DEFAULT 0
);

DROP TABLE IF EXISTS "post_tag";
CREATE TABLE "post_tag" (
  "post_id" INTEGER NOT NULL,
  "tag_id"  INTEGER NOT NULL,
  PRIMARY KEY ("post_id", "tag_id")
);

DROP TABLE IF EXISTS "image";
CREATE TABLE "image" (
  "id"        INTEGER NOT NULL PRIMARY KEY,
  "name"      VARCHAR (12)   NOT NULL,
  "post_id"   INTEGER DEFAULT 0
);