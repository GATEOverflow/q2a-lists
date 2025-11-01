# 🗂️ Q2A Lists Plugin

The **Lists Plugin** extends Question2Answer (Q2A) by allowing users to organize their favorite questions into customizable lists.  
It also provides admin-level control over how many lists users can have, which ones can be renamed, and synchronization with the built-in “Favorites” feature.

---

## Features Overview

### **Admin Controls**
- **Configurable number of lists:**  
  The admin can set how many lists each user can have (up to a hard limit of **10**).
  
- **Renamable lists:**  
  The admin can decide which lists are allowed to be renamed by users.
  
- **Favorite list synchronization:**  
  The **Favorite** list is automatically populated with each user’s Q2A favorite questions.  
  The admin can resync the favorite list anytime to match the site’s favorite questions.

- **List migration and deletion:**  
  If the admin wishes to remove a list, all of its questions can be **migrated** to another list before deletion — ensuring no data is lost.

---

### 👤 **User Functionality**

- **Multiple question lists:**  
  Users can create and manage multiple question lists (up to the number set by admin).

- **Add questions easily:**  
  From the question view page, users can use the **Lists button** to assign that question to one or more lists.

- **Automatic favorite syncing:**  
  If a question is added to the **Favorite list**, it is automatically marked as a site “Favorite” for that user.

- **Rename and visibility control:**  
  Users can **rename** their lists (if permitted by admin) and **toggle public/private** visibility for each list.

- **Automatic updates on merged questions:**  
  If a question in a list is merged with another, the list automatically updates to reference the new question.

- **Public lists:**  
  Lists marked as *Public* can be viewed by anyone (similar to viewing another user’s favorites).

- **Category filtering:**  
  Within a list view, users can click a **category name** to filter and view only the questions in that category within the same list.

---
## 🔗 URL Structure

| Purpose | Example |
|----------|----------|
| View user’s lists | `/userlists/<username>` |
| View specific list | `/userlists/<username>/<listid>` |
| View list filtered by category | `/userlists/<username>/<listid>/<category>` |

---

## 💡 Usage Notes

- Public lists can be viewed by anyone, but private lists are restricted to the list owner or admins.
- When the admin reduces the number of lists, any excess list questions should be **migrated first** to prevent loss.