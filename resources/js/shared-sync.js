(() => {
  "use strict";

  const channelName = "smms-hrms-live-sync";
  const sourceId = `${Date.now()}-${Math.random().toString(36).slice(2)}`;
  const reloadDelay = 80;
  let reloadTimer = null;
  let channel = null;

  function reloadForRemoteChange() {
    if (reloadTimer !== null) return;

    reloadTimer = window.setTimeout(() => {
      reloadTimer = null;
      window.location.reload();
    }, reloadDelay);
  }

  function broadcastChange(key, newValue, oldValue) {
    const message = { key, newValue, oldValue, sourceId };

    window.dispatchEvent(new CustomEvent("hrms:storage", { detail: message }));

    if (channel) {
      channel.postMessage(message);
    }
  }

  function handleRemoteChange(message) {
    if (!message || message.sourceId === sourceId) return;

    const event = new CustomEvent("hrms:storage", {
      detail: message,
      cancelable: true,
    });
    window.dispatchEvent(event);

    if (!event.defaultPrevented) {
      reloadForRemoteChange();
    }
  }

  try {
    channel = new BroadcastChannel(channelName);
    channel.addEventListener("message", (event) => {
      handleRemoteChange(event.data);
    });
  } catch (error) {
    channel = null;
  }

  window.addEventListener("storage", (event) => {
    handleRemoteChange({
      key: event.key,
      newValue: event.newValue,
      oldValue: event.oldValue,
      sourceId: null,
    });
  });

  const storagePrototype = Object.getPrototypeOf(window.localStorage);
  const originalSetItem = storagePrototype.setItem;
  const originalRemoveItem = storagePrototype.removeItem;
  const originalClear = storagePrototype.clear;

  storagePrototype.setItem = function setItem(key, value) {
    const oldValue = this.getItem(key);
    originalSetItem.call(this, key, value);

    if (this === window.localStorage && oldValue !== String(value)) {
      broadcastChange(String(key), String(value), oldValue);
    }
  };

  storagePrototype.removeItem = function removeItem(key) {
    const oldValue = this.getItem(key);
    originalRemoveItem.call(this, key);

    if (this === window.localStorage && oldValue !== null) {
      broadcastChange(String(key), null, oldValue);
    }
  };

  storagePrototype.clear = function clear() {
    const hadValues = this.length > 0;
    originalClear.call(this);

    if (this === window.localStorage && hadValues) {
      broadcastChange(null, null, null);
    }
  };

  const accountRoles = {
    EMPLOYEE: { id: "EMP-234482", role: "Employee" },
    HR: { id: "HR-321942", role: "HR" },
    ADMIN: { id: "ADMIN-094773", role: "Admin" },
    ACCOUNTING: { id: "ACC-054535", role: "Accounting" },
  };

  function getAccountIdentity() {
    const folder = window.location.pathname.split("/").filter(Boolean).slice(-2, -1)[0] || "";
    const rolePrefix = folder.split("_")[0].toUpperCase();
    return accountRoles[rolePrefix] || null;
  }

  function getSavedAccountProfile(employeeId) {
    const storedProfile = window.localStorage.getItem(`myAccountProfile:${employeeId}`);
    if (!storedProfile) return null;

    try {
      return JSON.parse(storedProfile);
    } catch (error) {
      console.error(`Unable to read account profile for ${employeeId}.`, error);
      return null;
    }
  }

  function setupAccountMenu() {
    const profileMenu = document.getElementById("profileMenu");
    const profileButton = document.getElementById("profileToggle");
    const identity = getAccountIdentity();
    const profileName = profileButton?.querySelector(".profile-name");
    const profileTitle = profileButton?.querySelector(".profile-info small");
    const avatar = profileButton?.querySelector(".avatar");
    if (!profileMenu || !profileButton || !identity || !profileName) return;
    const honorific = profileName.textContent.trim().match(/^(Mr\.?|Ms\.?|Mrs\.?)\s+/i)?.[0] || "";

    const accountLink = document.createElement("a");
    accountLink.href = "../MY_ACCOUNT/MY_ACCOUNT.html";
    accountLink.className = "profile-menu-item";
    accountLink.textContent = "My Account";
    accountLink.addEventListener("click", (event) => {
      event.preventDefault();
      const context = {
        employeeId: identity.id,
        role: identity.role,
        name: profileName.textContent.trim().replace(/^(Mr\.?|Ms\.?|Mrs\.?)\s+/i, ""),
        title: profileTitle?.textContent.trim() || identity.role,
        returnUrl: window.location.href,
      };
      window.sessionStorage.setItem("myAccountContext", JSON.stringify(context));
      window.location.href = accountLink.href;
    });
    const helpLink = profileMenu.querySelector(".profile-menu-item");
    profileMenu.insertBefore(accountLink, helpLink || profileMenu.firstChild);

    const savedProfile = getSavedAccountProfile(identity.id);
    if (savedProfile?.name) {
      profileName.textContent = `${honorific}${savedProfile.name}`;
      if (avatar) {
        avatar.textContent = savedProfile.name
          .split(/\s+/)
          .filter(Boolean)
          .slice(0, 2)
          .map((part) => part[0])
          .join("")
          .toUpperCase();
      }
    }
  }

  setupAccountMenu();

  window.HRMSRealtime = Object.freeze({
    channelName,
    sourceId,
    refresh: reloadForRemoteChange,
  });
})();
