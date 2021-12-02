BookMark - Bright Bookshelf
====

Hanyang University, SE/AI project
---

---

**Quick links**

GUI mockups [(Figma)](https://www.figma.com/file/txDJQqtWLzodwYxILwvgNh/Bookshelf)

UML [(draw.io)](https://drive.google.com/file/d/1qXDdPbP0vvrqVYIyJdMQ6C-DeG7IgjeZ/view?usp=sharing)

Book Recommendation System Dataset [(Kaggle)](https://www.kaggle.com/fahadmehfoooz/book-recommendation-system/data)

---

**Contributors**

The contributors of this project are:

- Mathilde Lærke Hansen, 9077620215, SE
  Department of Information Systems
- Sarah Schlegel, 9091820217, SE & AI
  Department of Computer Science
- Anais Zhang, 9088520214, SE & AI
  Department of Computer Science
- YoungHa Hwang, 2017029261, SE
  Department of Information Systems
- Laura Vikke Mårtensson, 9077020219, SE
  Department of Computer Science

This team has been created for a joint project between the Software Engineering (SE) and the Artificial Intelligence and Application (AI) courses in Hanyang University.



---

I. Introduction
----

**Abstract**

*Nowadays, modern people (workers, students, etc.) lack time for reading books. Although they have motivation to read, the time for reading has low priority amongst the 24 hours of the day when people are investing time in various activities such as studying, working, or exercising. For example, even if you want to read a book, the hardships of choosing and purchasing a book yourself are an obstacle to reading.*

*Therefore, we created the application BookMark to overcome these difficulties. BookMark is meant to help the customers manage their bookshelf, either physical or digital, and arrange more time in their daily life to read. It provides a system of notifications and reminders to keep track of the books the user is currently reading. It makes the bookshelf management easier by using simple image recognition to search for a book by its cover or barcode or ISBN number, automatically sorting the content and providing different pairing options between physical copies, ebooks or audiobooks. By automatizing the usually difficult part of book management, BookMark wishes to let the reader make more space in his or her day to actually read.*



**Motivation**

The **BookMark** project comes from the observation that many people today don't have the time or the motivation to read new books as they navigate their fast paced and overstimulated everyday life. Our goal is to provide with this smart bookshelf and application a simpler way to manage the books people are reading, whether it's by swapping from the physical book to the audiobook, by reminding them to read at certain times or by recommending them new books based on their current preferences, or by recommending new books to the users.

In the AI part, we will mainly focus on the book management and suggestion. We will be building some prediction algorithms, based on graphs, schemes and statistics from existing libraries and APIs, to determine the closest books to be recommended to a specific user. In the end, we would like to create a good recommendation system for the user.

We would also like to include some image recognition libraries to scan the barcode or the ISBN number and recover the book data from that information, or to scan the book cover and search for the book from those informations. 



---

II. Dataset
---

We will be using the Goodreads-books Dataset [1] that can be found on Kaggle. This dataset comes from the Goodreads API, which is a software already in use that we looked at when preparing this project. A few adjustments were made directly on the dataset where some misplaced `,`created reading issues for `pandas`, but that issue has been quickly solved by removing the problematic commas.



---

III. Methodology
---

The approach we have chosen is a Collaborative Filtering algorithm. The idea is to consider similarities between users and at the same time similarities between items (here the books) to provide recommendations. The collaborative filtering approach is for example a part of Netflix's recommendation algorithm, even though it is combined with many other approaches.

Collaborative filtering relies on the idea that if two users have a similar ranking of items, they will probably act similarly in the future, and thus 

- Explaining your choice of algorithms (methods) 
- Explaining features or code (if any)



---

IV. Evaluation & Analysis
---

- Graphs, tables, any statistics (if any)



---

V. References
---

Related work, existing studies, documentation, blogs…

1) Book Recommendation System Dataset on [Kaggle](https://www.kaggle.com/fahadmehfoooz/book-recommendation-system/data) by Fahad Mehfooz
2)  [Collaborative Filtering: Google Developers Documentation](https://developers.google.com/machine-learning/recommendation/collaborative/matrix)
3) *Understand Netflix's recommendation algorithm* (French article), Tidiane CISSE & Yasmine BELHADRI, published on [Les Mondes Numériques](https://www.lesmondesnumeriques.net/2019/02/02/comprendre-lalgorithme-de-recommandation-de-netflix/) on February 2nd, 2019

- Tools, libraries, blogs, or any documentation that you have used to do this project.



---

VI. Conclusion
---

Discussion

