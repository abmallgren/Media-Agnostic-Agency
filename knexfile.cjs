const path = require('path');

/** @type {import('knex').Knex.Config} */
module.exports = {
  client: 'mysql2',
  connection: {
    host: process.env.DB_HOST || 'localhost',
    port: Number(process.env.DB_PORT || 3306),
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || 'P@$$w0rd',
    database: process.env.DB_NAME || 'maa'
  },
  migrations: {
    tableName: 'knex_migrations',
    directory: './db/migrations'
  }
};