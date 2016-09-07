#Neno [![Build Status](https://travis-ci.org/Jensen-Technologies/neno.svg?branch=master)](https://travis-ci.org/Jensen-Technologies/neno) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Jensen-Technologies/neno/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Jensen-Technologies/neno/?branch=master)

Neno is a translation solution for Joomla. It allows you to translate by yourself or order external professional or machine translation with the click of a button.

Documentation: http://documentation.neno-translate.com

# **How to contribute**

Thank you for your interest in improving Neno Translate for Joomla. No matter how small or how big your contribution is, you have the potential to make a positive impact on hundreds of people. In this guide we are going to answer any questions you may have on how to contribute.

### **Which code should I be using?**

Neno Translate for Joomla has its code in a separate Git repository. You can access it at [https://github.com/Jensen-Technologies/neno](https://github.com/Jensen-Technologies/neno).

If you want to work on the code or the documentation we kindly ask that you **always** use the latest [development branch](https://github.com/Jensen-Technologies/neno/tree/stagging) on our Git repository. Before starting to fix / improve something make sure it's not already taken care of in this branch. 

**How can I contribute?**

contributing code is not the only way to contribute. Contributions come in many shapes and forms:

* **Docblocks**. The code should be self-documenting, so we have docblocks in the top of each file, class and method. Despite our best intentions, sometimes something is missing or is not up to date. Keeping Docbloks up to date is a big help

* **Code style**. When our code follows the Joomla standards we're making it easier for other developers read it and improve upon it. If you have spotted some code that does not follow those standards fixing it will also be a big help.

* **Code**. Fixed a bug? Created a feature? Improved a sample component? Send a PR, explain what you did (and why) and you're instantly helping scores of people.

# How to perform each of these contributions.

#### **Code, docblocks and code style**

First make sure that you have checked the latest development branch. Changes happen on a daily basis, often many times a day. Checking out the latest development branch will ensure you're not trying to improve something already taken care of.

If you are going to work on docblocks or code style, please be careful not to make any accidental code changes. 

If you're dealing with docblocks it's easy for people with commit access to spot any issues; if they see a change in code lines they will know that they have to skip that when committing. Code style changes however are much tougher to fix as the committer has to go through the original and modified file line-by-line to make sure nothing got inadvertently changed. 

In order to help them please make only small changes at any one time, ideally up to 100 lines of code or less. If you want to make many changes in many files, break your work into smaller chunks. If unsure on what to do, ask first.

If you are working on a code change it's always a good idea to first discuss this on the with @vistiyos. He's the lead developer of Neno Translate for Joomla and he's the most qualified to tell you if your intended change is something that can be included and in which version. Usually changes are included right away, unless there are backwards compatibility issues.

Once you have made your changes please make sure you read the "How do I submit a Pull Request" section to find out how to add your changes to the project.

### **How do I submit a Pull Request (PR)?**

First, you will need a GitHub user account. If you don't have one already, go to [https://github.com/](github.com), create your free account and log in.

You will need to fork our Git repository. To do this, go to [https://github.com/Jensen-Technologies/neno](https://github.com/Jensen-Technologies/neno) and click the Fork button towards the upper right hand corner of the page. This will fork Neno Translate for Joomla repository under your GitHub account.

Make sure you clone the repository (the one under *your* account) on your computer. If you're not a heavy Git user don't worry, you can use the GitHub application on your Mac or Windows computer. If you're a Linux user you can just use the command line or your favourite Git client application.

Before making any changes you will need to create a new branch. In the GitHub application you need to first go into your repository and click the branch name. Initially you need to click on *"stagging"* to ensure that you are seeing the development, not the master, branch. Then click on it again and type in the name of the new branch, then press Enter. You can now make all of your changes in this branch.

After you're done making changes you will need to publish your branch back to GitHub. If you're using the GitHub application you can do this in just two steps. First commit all your changed files, which adds them to your local branch. Then click on the Sync Branch button. When it stops spinning everything is uploaded to GitHub and you're ready to finally do your Pull Request.

Now go to github.com, into the forked Neno repository under your user account. Click on the branch dropdown and select your branch. On the left you will see a green icon with the tooltip "Compare & Review". Click it. Just fill in the title and description, giving as much information as possible about what you did and why, and your PR is now created. If you need to explain something in greater detail just send a list message.

