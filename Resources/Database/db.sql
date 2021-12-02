USE bookmark;

CREATE TABLE bookshelf (
    id integer auto_increment primary key,
    width integer not null,
    height integer not null,
    depth integer not null,
    shelves_number integer not null,
    sorting varchar(16)
);

CREATE TABLE language (
    id integer auto_increment primary key,
    lang varchar(32)
);

CREATE TABLE genre (
    id integer auto_increment primary key,
    genre varchar(32)
);

CREATE TABLE book (
    isbn13 varchar(17) primary key,
    isbn11 varchar(11),
    series_name varchar(64),
    series_position integer,
    book_name varchar(64) not null,
    date_published date,
    pages integer default 0,
    genre_id integer references genre (id),
    language_id integer references language (id)
);

CREATE TABLE author (
    id integer auto_increment primary key,
    name varchar(32) not null,
    date_of_birth date
);

CREATE TABLE book_author (
    book_id varchar(17) references book (isbn13),
    author_id integer references author (id),
    primary key (book_id, author_id)
);

CREATE TABLE cover (
    id integer auto_increment primary key,
    image_path varchar(32) not null,
    book_id varchar(17) references book (isbn13)
);

CREATE TABLE book_in_shelf (
    book_id varchar(17) references book (isbn13),
    bookshelf_id integer references booksh (id),
    shelf_number integer not null,
    shelf_position integer not null,
    primary key (book_id, bookshelf_id)
);

CREATE TABLE user (
    id integer auto_increment primary key,
    username varchar(32) not null,
    password varchar(512) not null,
    email varchar(64) not null,
    phone varchar(15),
    token varchar(8),
    classification enum('author', 'genre', 'title', 'none') default 'none',
    settings_language_id integer references language (id),
    reading_language_id integer references language (id),
    bookshelf_id varchar(32) references bookshelf (id)
);

CREATE TABLE status (
    id integer auto_increment primary key,
    label varchar(16) not null,
    color char(6) default 'ccccff'
);

CREATE TABLE reading_status (
    id integer auto_increment primary key,
    current_page integer default 0,
    current_chapter integer default 0,
    date_finished date default null,
    grade float default 0 check (grade <= 5.0),
    notes text,
    status_id integer references status (id),
    book_id varchar(17) references book (isbn13),
    user_id integer references user (id)
);

CREATE TABLE reminder (
    id integer auto_increment primary key,
    remind_time time,
    daily boolean default true,
    weekday integer default 0,
    active boolean default true
);

CREATE TABLE user_reminder (
    reminder_id integer references reminder (id),
    user_id integer references user (id),
    primary key (reminder_id, user_id)
);

CREATE TABLE book_reminder (
    reminder_id integer references reminder (id),
    status_id integer references status (id),
    primary key (reminder_id, status_id)
);
