#pragma semicolon 1

#include <sourcemod>
#include <sdktools>

#define PLUGIN_NAME "Donations Control Plugin"
#define PLUGIN_VERSION "1.0.1"

ConVar hConVars[6];

bool cv_bEnabled, cv_bFullScreen, cv_bAdvertStatus;
char cv_sURL[255];
float cv_fAdvertTime;
int cv_iMinimumAmount;

Menu hDisplayMenu;
Handle hAdvertTimer;

public Plugin myinfo = {
	name 		= PLUGIN_NAME,
	author 		= "Keith Warren (Drixevel), NineteenEleven & Sgt. Gremulock",
	description = "Allows clients to donate via the Donations Control Panel script in-game (syntax updated by Sgt. Gremulock).",
	version 	= PLUGIN_VERSION,
	url 		= "http://www.drixevel.com/"
};

public void OnPluginStart()
{
	LoadTranslations("common.phrases");
	LoadTranslations("donationcontrol.phrases");

	CreateConVar("donations_control_version", PLUGIN_VERSION, PLUGIN_NAME, FCVAR_REPLICATED|FCVAR_NOTIFY|FCVAR_DONTRECORD);
	hConVars[0] = CreateConVar("sm_kpanel_enable", "1", "Enable or disable plugin", _, true, 0.0, true, 1.0);
	hConVars[1] = CreateConVar("sm_kpanel_url", "https://website.com/donations", "URL to your Donations Control installation.");
	hConVars[2] = CreateConVar("sm_kpanel_fullscreen", "1", "Enable or disable fullscreen windows", _, true, 0.0, true, 1.0);
	hConVars[3] = CreateConVar("sm_kpanel_advertisement", "1", "Display plugin creator advertisement: (1 = on, 0 = off)", _, true, 0.0, true, 1.0);
	hConVars[4] = CreateConVar("sm_kpanel_advertisement_time", "120.0", "Timer between messages: (1.0 + )", _, true, 1.0);
	hConVars[5] = CreateConVar("sm_kpanel_minimum", "4", "Minimum amount to donate: (Default: 4, Less than or equal to)", _, true, 1.0);

	for (int i = 0; i < sizeof(hConVars); i++)
	{
		hConVars[i].AddChangeHook(HandleCvars);
	}

	RegConsoleCmd("sm_donate", DonatePanel);

	AutoExecConfig();
}

public void OnConfigsExecuted()
{
	cv_bEnabled 		= hConVars[0].BoolValue;
	hConVars[1].GetString(cv_sURL, sizeof(cv_sURL));
	cv_bFullScreen 		= hConVars[2].BoolValue;
	cv_bAdvertStatus 	= hConVars[3].BoolValue;
	cv_fAdvertTime 		= hConVars[4].FloatValue;
	cv_iMinimumAmount 	= hConVars[5].IntValue;

	if (cv_bEnabled)
	{
		if (cv_bAdvertStatus && cv_fAdvertTime > 1.0)
		{
			ClearTimer(hAdvertTimer);
			hAdvertTimer = CreateTimer(cv_fAdvertTime, TimerAdvertisement, _, TIMER_REPEAT);
		}

		hDisplayMenu = new Menu(MenuHandle);
		hDisplayMenu.SetTitle("%s", "Menu Title");
		hDisplayMenu.AddItem("5", "5");
		hDisplayMenu.AddItem("10", "10");
		hDisplayMenu.AddItem("15", "15");
		hDisplayMenu.AddItem("20", "20");
	}
}

public void HandleCvars(ConVar cvar, const char[] sOldValue, const char[] sNewValue)
{
	if (StrEqual(sOldValue, sNewValue, true))
		return;

	int iNewValue = StringToInt(sNewValue);

	if (cvar == hConVars[0])
	{
		cv_bEnabled = view_as<bool>(iNewValue);
		if (cv_bEnabled)
			hAdvertTimer = CreateTimer(cv_fAdvertTime, TimerAdvertisement, _, TIMER_REPEAT);
		else {
			ClearTimer(hAdvertTimer);
		}
	}

	if (cvar == hConVars[1])
		strcopy(cv_sURL, sizeof(cv_sURL), sNewValue);

	if (cvar == hConVars[2])
		cv_bFullScreen = view_as<bool>(iNewValue);

	if (cvar == hConVars[3])
	{
		cv_bAdvertStatus = view_as<bool>(iNewValue);
		if (cv_bAdvertStatus)
			hAdvertTimer = CreateTimer(cv_fAdvertTime, TimerAdvertisement, _, TIMER_REPEAT);
		else {
			ClearTimer(hAdvertTimer);
		}
	}

	if (cvar == hConVars[4])
	{
		cv_fAdvertTime = StringToFloat(sNewValue);
		ClearTimer(hAdvertTimer);
		hAdvertTimer = CreateTimer(cv_fAdvertTime, TimerAdvertisement, _, TIMER_REPEAT);
	}
}

public Action DonatePanel(int client, int args)
{
	if (!cv_bEnabled || !IsClientInGame(client))
		return Plugin_Handled;

	if (!args)
		DisplayMenu(hDisplayMenu, client, 30);
	else {
		char sArg[32];
		GetCmdArg(1, sArg, sizeof(sArg));
		OpenDonationWindow(client, StringToInt(sArg));
	}

	return Plugin_Handled;
}

public int MenuHandle(Menu menu, MenuAction action, int param1, int param2)
{
	switch(action)
	{
		case MenuAction_Select:
		{
			char sInfo[32];
			menu.GetItem(param2, sInfo, sizeof(sInfo));
			OpenDonationWindow(param1, StringToInt(sInfo));
		}
	}
}

void OpenDonationWindow(int client, int amount)
{
	if (cv_iMinimumAmount <= amount)
	{
		char SteamID[32], donateamount[5], donateurl[128];
		GetClientAuthId(client, AuthId_Steam2, SteamID, sizeof(SteamID));
		ReplaceString(SteamID, sizeof(SteamID), ":", "%3A");

		IntToString(amount, donateamount, sizeof(donateamount));

		strcopy(donateurl, sizeof(donateurl), cv_sURL);
		Format(donateurl, sizeof(donateurl), "%s/donate.php?&amount=%s&tier=1&steamid_user=%s", donateurl, donateamount, SteamID);

		KeyValues kv = new KeyValues("motd");
		kv.SetString("title", "Backpack");
		kv.SetNum("type", MOTDPANEL_TYPE_URL);
		kv.SetString("msg", donateurl);

		if (cv_bFullScreen)
			kv.SetNum("customsvr", 1);

		ShowVGUIPanel(client, "info", kv);
		kv.Close();
	} else {
		PrintToChat(client, "Minimum amount is 5 dollars");
	}
}

void ClearTimer(Handle hTimer)
{
	if (hTimer != null)
	{
		KillTimer(hTimer);
		hTimer = null;
	}
}

public Action TimerAdvertisement(Handle timer)
{
	PrintToChatAll("%s", "Advertisement Message");
}
